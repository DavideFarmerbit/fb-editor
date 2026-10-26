<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\CoreBlocks;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Breakpoints\BreakpointConfig;
use Fb\CoreBlocks\BaseStylesheet;
use Fb\CoreBlocks\Library\Button\Button;
use Fb\CoreBlocks\Library\Paragraph\Paragraph;
use Fb\Output\BlockMarkupCleaner;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Theme\ThemeConfigFile;
use PHPUnit\Framework\TestCase;

/**
 * Core-rendered markup in → fb classes and page CSS out, as on the frontend.
 */
final class CoreBlockRenderTest extends TestCase
{
	private const array HOOKS = [ 'render_block_core/button', 'render_block_core/paragraph' ];

	private InstanceStyleCollector $collector;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		// The active plugin's own cleaner is hooked too: test this one alone.
		foreach ( self::HOOKS as $hook ) {
			remove_all_filters( $hook );
		}

		$config          = new BreakpointConfig();
		$defaults        = new DefaultsRepository( new ThemeConfigFile(), $config, 'fb-phpunit-theme' );
		$this->collector = new InstanceStyleCollector( $config, $defaults );
		$registry        = new BlockRegistry( new BlockRenderer( $config, $this->collector, $defaults ), [], [ new Paragraph(), new Button() ] );
		( new BlockMarkupCleaner( $registry, $config, $this->collector ) )->register();
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
				'fb'        => [ 'fontWeight' => [ 'desktop' => '700' ] ],
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
		self::assertSame( "@layer fb.instances{:where(.{$instance}>.fb-button__link){font-weight:700;}}", $this->collector->css() );
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
