<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Defaults\BlockDefaultsPostType;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Defaults\DefaultsStylesheet;
use Fb\Blocks\Library\Container\Container;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Tests\Fixtures\TestRendering;
use Fb\Theme\ThemeConfigFile;
use PHPUnit\Framework\TestCase;

/**
 * Defaults end to end, against the real database: saved posts are
 * sanitized, merged over the theme's fb.json, and printed in fb.defaults.
 */
final class DefaultsTest extends TestCase
{
	/** A theme that doesn't exist: tests never meet real saved defaults. */
	private const string THEME = 'fb-phpunit-theme';

	private BreakpointConfig $breakpoints;
	private Container $container;
	private ?int $postId = null;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		$this->breakpoints = new BreakpointConfig();
		$this->container   = new Container();

		// Sanitizing on save needs the post type hooks.
		$registry = new BlockRegistry( $this->rendering()->renderer, [ $this->container ] );
		( new BlockDefaultsPostType( $registry, $this->breakpoints ) )->register();
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
		return new TestRendering( $this->breakpoints, dirname( __DIR__, 2 ) . '/Fixtures/fb-defaults.json' );
	}

	private function repository(): DefaultsRepository
	{
		return $this->rendering()->defaults;
	}

	private function save( string $content ): void
	{
		$this->postId = (int) wp_insert_post(
			[
				'post_type'    => BlockDefaultsPostType::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'fb/container',
				'post_name'    => BlockDefaultsPostType::slug( $this->container->definition()->name, self::THEME ),
				'post_content' => wp_slash( $content ),
			]
		);
	}

	public function testSavedContentIsSanitizedThroughTheDefinition(): void
	{
		$this->save( '{"base":{"padding":{"desktop":{"top":{"kind":"custom","value":-5,"unit":"px"},"left":{"kind":"auto"},"right":{"kind":"custom","value":8,"unit":"px"}}},"evil":"<script>"},"evil":1}' );

		self::assertSame( '{"base":{"padding":{"desktop":{"right":{"kind":"custom","value":8.0,"unit":"px"}}}}}', get_post_field( 'post_content', $this->postId ) );
	}

	public function testSavedDefaultsOverlayTheThemeDefaults(): void
	{
		$this->save( '{"base":{"padding":{"mobile":{"top":{"kind":"custom","value":24,"unit":"px"}}}}}' );

		self::assertSame(
			// Theme desktop + saved mobile (beats theme mobile) + theme display.
			'{"base":{"display":{"desktop":"flex"},"padding":{"desktop":{"top":{"kind":"custom","value":40.0,"unit":"px"}},"mobile":{"top":{"kind":"custom","value":24.0,"unit":"px"}}}}}',
			wp_json_encode( $this->repository()->for( $this->container->definition() ), JSON_PRESERVE_ZERO_FRACTION )
		);
	}

	public function testDefaultsAreCompiledOnTheBlockClass(): void
	{
		self::assertSame(
			'@layer fb.defaults{:where(.fb-container){display:flex;--fb-padding-top:40px;}@media (max-width: 767px){:where(.fb-container){--fb-padding-top:16px;}}}',
			( new DefaultsStylesheet( $this->repository(), $this->breakpoints ) )->css( [ $this->container->definition() ] )
		);
	}

	public function testOnlyThemeEditorsCanReadDefaultsOverRest(): void
	{
		$this->save( '{}' );
		$route = '/wp/v2/' . BlockDefaultsPostType::REST_BASE;

		wp_set_current_user( 0 );
		self::assertSame( 401, rest_do_request( new \WP_REST_Request( 'GET', $route ) )->get_status() );
		self::assertSame( 401, rest_do_request( new \WP_REST_Request( 'GET', "{$route}/{$this->postId}" ) )->get_status() );
	}

	public function testRenderFallsBackToTheDefaultTag(): void
	{
		$this->save( '{"base":{"tag":"aside"}}' );
		$rendering = $this->rendering();

		self::assertStringStartsWith( '<aside class="fb-container', $rendering->render( $this->container, [], '' ) );
		self::assertStringStartsWith( '<main class="fb-container', $rendering->render( $this->container, [ 'fb' => [ 'base' => [ 'tag' => 'main' ] ] ], '' ) );
	}

	public function testNoBlocksOnThePageMeansNoDefaultsCss(): void
	{
		self::assertSame( '', ( new DefaultsStylesheet( $this->repository(), $this->breakpoints ) )->css( [] ) );
	}
}
