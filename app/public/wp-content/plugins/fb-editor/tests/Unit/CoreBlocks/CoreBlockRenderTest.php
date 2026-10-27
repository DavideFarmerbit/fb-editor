<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\CoreBlocks;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Blocks\BaseStyles\BaseStylesheet;
use Fb\CoreBlocks\Library\Button\Button;
use Fb\CoreBlocks\Library\Paragraph\Paragraph;
use Fb\Blocks\Variants\VariantsStylesheet;
use Fb\Output\BlockMarkupCleaner;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Tests\Fixtures\TestRendering;
use Fb\Theme\ThemeConfigFile;
use PHPUnit\Framework\TestCase;

/**
 * Core-rendered markup in → fb classes and page CSS out, as on the frontend.
 */
final class CoreBlockRenderTest extends TestCase
{
	private const array HOOKS = [ 'render_block_core/button', 'render_block_core/paragraph' ];

	private InstanceStyleCollector $collector;
	private TestRendering $rendering;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		// The active plugin's own cleaner is hooked too: test this one alone.
		foreach ( self::HOOKS as $hook ) {
			remove_all_filters( $hook );
		}

		$this->rendering = new TestRendering( new BreakpointConfig(), dirname( __DIR__, 2 ) . '/Fixtures/fb-variants.json' );
		$this->collector = $this->rendering->collector;
		$registry        = new BlockRegistry( $this->rendering->renderer, [], [ new Paragraph(), new Button() ] );
		( new BlockMarkupCleaner( $registry, $this->rendering->styler ) )->register();
	}

	protected function tearDown(): void
	{
		remove_all_filters( 'fb_breakpoints' );
		foreach ( self::HOOKS as $hook ) {
			remove_all_filters( $hook );
		}
	}

	public function testBlockWithoutValuesGetsOnlyItsClass(): void
	{
		self::assertSame(
			'<p class="fb-paragraph">x</p>',
			apply_filters( 'render_block_core/paragraph', '<p class="wp-block-paragraph has-text-align-center">x</p>', [ 'attrs' => [] ] )
		);
		self::assertSame( '', $this->collector->css() );
	}

	public function testElementControlsStyleTheChildAndKeepUserClasses(): void
	{
		$block = [
			'attrs' => [
				'fb'        => [
					'base'   => [ 'fontWeight' => [ 'desktop' => '700' ] ],
					'states' => [ 'hover' => [ 'fontWeight' => [ 'desktop' => '800' ] ] ],
				],
				'className' => 'is-style-outline cta',
			],
		];

		$html = apply_filters(
			'render_block_core/button',
			'<div class="wp-block-button is-style-outline cta"><a class="wp-block-button__link wp-element-button" href="#">Go</a></div>',
			$block
		);

		self::assertIsString( $html );
		self::assertMatchesRegularExpression( '#^<div class="fb-button (fb-[0-9a-z]{7}) cta"><a class="fb-button__link" href="\#">Go</a></div>$#', $html );
		self::assertSame( 1, preg_match( '#fb-button (fb-[0-9a-z]{7})#', $html, $match ) );
		$instance = $match[1] ?? '';
		self::assertSame(
			"@layer fb.instances{:where(.{$instance}>.fb-button__link){font-weight:700;}}"
			. "@layer fb.states.instances{:where(.{$instance}:hover>.fb-button__link){font-weight:800;}}",
			$this->collector->css()
		);
	}

	public function testVariantAddsItsClassAndItsCss(): void
	{
		$html = apply_filters(
			'render_block_core/button',
			'<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Go</a></div>',
			[ 'attrs' => [ 'fb' => [ 'variant' => 'primary' ] ] ]
		);

		// Nothing of its own: no instance class.
		self::assertSame( '<div class="fb-button fb-button--primary"><a class="fb-button__link" href="#">Go</a></div>', $html );
		self::assertSame( '', $this->collector->css() );
		self::assertSame(
			'@layer fb.variants{:where(.fb-button--primary>.fb-button__link){font-weight:600;}}'
			. '@layer fb.states.variants{:where(.fb-button--primary:hover>.fb-button__link){font-weight:800;}}',
			( new VariantsStylesheet( $this->rendering->defaults, new BreakpointConfig() ) )->css( $this->collector->variants() )
		);
	}

	public function testUnknownVariantIsIgnored(): void
	{
		$html = apply_filters(
			'render_block_core/button',
			'<div class="wp-block-button"><a class="wp-block-button__link" href="#">Go</a></div>',
			[ 'attrs' => [ 'fb' => [ 'variant' => 'gone' ] ] ]
		);

		self::assertSame( '<div class="fb-button"><a class="fb-button__link" href="#">Go</a></div>', $html );
		self::assertSame( [], $this->collector->variants() );
	}

	public function testBaseStylesUseCoreElementClassesInTheEditor(): void
	{
		$stylesheet = new BaseStylesheet();

		self::assertSame(
			'@layer fb.base{:where(.fb-button>.fb-button__link){display:inline-block;text-decoration:none;}}',
			$stylesheet->css( [ new Button() ], false )
		);
		self::assertSame(
			'@layer fb.base{:where(.fb-button>.wp-block-button__link){display:inline-block;text-decoration:none;}}',
			$stylesheet->css( [ new Button() ], true )
		);
	}
}
