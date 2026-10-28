<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Variants\BlockVariantsPostType;
use Fb\Blocks\Variants\VariantSlug;
use Fb\Breakpoints\BreakpointConfig;
use Fb\CoreBlocks\Library\Button\Button;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Tests\Fixtures\TestRendering;
use PHPUnit\Framework\TestCase;

/**
 * Variants end to end, against the real database: theme variants from
 * fb.json, saved ones sanitized and laid over them by slug.
 */
final class VariantsTest extends TestCase
{
	private BreakpointConfig $breakpoints;
	private Button $button;
	private ?int $postId = null;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		$this->breakpoints = new BreakpointConfig();
		$this->button      = new Button();

		// Sanitizing on save needs the post type hooks.
		$registry = new BlockRegistry( $this->rendering()->renderer, [], [ $this->button ] );
		( new BlockVariantsPostType( $registry, $this->breakpoints ) )->register();
		do_action( 'init' );
	}

	protected function tearDown(): void
	{
		if ( null !== $this->postId ) {
			wp_delete_post( $this->postId, true );
		}
		remove_all_filters( 'fb_breakpoints' );
		remove_all_filters( 'wp_insert_post_data' );
	}

	private function rendering(): TestRendering
	{
		return new TestRendering( $this->breakpoints, dirname( __DIR__, 2 ) . '/Fixtures/fb-variants.json' );
	}

	private function save( string $content ): void
	{
		$this->postId = (int) wp_insert_post(
			[
				'post_type'    => BlockVariantsPostType::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'core/button',
				'post_name'    => BlockVariantsPostType::slug( $this->button->definition()->name, TestRendering::THEME ),
				'post_content' => wp_slash( $content ),
			]
		);
	}

	public function testThemeVariantsDropInvalidEntries(): void
	{
		self::assertSame(
			'[{"slug":"primary","name":"Primary","values":{"looks":[{"values":{"fontWeight":{"desktop":"600"}}},{"states":["hover"],"values":{"fontWeight":{"desktop":"800"}}}]}},'
			. '{"slug":"ghost","name":"ghost","values":{}}]',
			wp_json_encode( $this->rendering()->variants->theme( $this->button->definition() ) )
		);
	}

	public function testSavedContentIsSanitized(): void
	{
		$this->save( '[{"slug":"big","name":"<b>Big</b>","values":{"looks":[{"values":{"fontSize":{"desktop":{"kind":"custom","value":2,"unit":"rem"}},"evil":1}}]},"x":1},{"slug":"--"},"nope"]' );

		self::assertSame(
			'[{"slug":"big","name":"Big","values":{"looks":[{"values":{"fontSize":{"desktop":{"kind":"custom","value":2.0,"unit":"rem"}}}}]}}]',
			get_post_field( 'post_content', $this->postId )
		);
	}

	public function testSavedVariantsOverlayThemeOnesBySlug(): void
	{
		$this->save( '[{"slug":"big","name":"Big"},{"slug":"primary","name":"Main","values":{"looks":[{"values":{"fontWeight":{"mobile":"700"}}}]}}]' );

		self::assertSame(
			'[{"slug":"primary","name":"Main","values":{"looks":[{"values":{"fontWeight":{"desktop":"600","mobile":"700"}}},{"states":["hover"],"values":{"fontWeight":{"desktop":"800"}}}]}},'
			. '{"slug":"ghost","name":"ghost","values":{}},'
			. '{"slug":"big","name":"Big","values":{}}]',
			wp_json_encode( $this->rendering()->variants->for( $this->button->definition() ) )
		);
		self::assertSame( 'Main', $this->rendering()->variants->find( $this->button->definition(), new VariantSlug( 'primary' ) )?->name );
	}
}
