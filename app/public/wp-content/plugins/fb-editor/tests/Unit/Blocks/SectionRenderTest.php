<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Library\Section\Section;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Theme\ThemeConfigFile;
use PHPUnit\Framework\TestCase;

/**
 * Raw attributes in → markup and page CSS out, as on the frontend.
 */
final class SectionRenderTest extends TestCase
{
	private InstanceStyleCollector $collector;
	private BlockRenderer $renderer;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		$config          = new BreakpointConfig();
		$defaults        = new DefaultsRepository( new ThemeConfigFile(), $config );
		$this->collector = new InstanceStyleCollector( $config, $defaults );
		$this->renderer  = new BlockRenderer( $config, $this->collector, $defaults );
	}

	protected function tearDown(): void
	{
		remove_all_filters( 'fb_breakpoints' );
	}

	public function testDefaultsToSectionWithoutInstanceClass(): void
	{
		self::assertSame(
			'<section class="fb-section"><div class="fb-section__inner"><p>x</p></div></section>',
			$this->renderer->render( new Section(), [], '<p>x</p>' )
		);
		self::assertSame( '', $this->collector->css() );
	}

	public function testRendersTagUserClassesAndInstanceClass(): void
	{
		$attributes = [
			'fb'        => [
				'base' => [
					'tag'     => 'header',
					'padding' => [ 'desktop' => [ 'top' => [ 'kind' => 'custom', 'value' => 8, 'unit' => 'px' ] ] ],
				],
			],
			'className' => 'hero "><script>',
		];

		$html = $this->renderer->render( new Section(), $attributes, '' );

		self::assertMatchesRegularExpression(
			'#^<header class="fb-section (fb-[0-9a-z]{7}) hero &quot;&gt;&lt;script&gt;"><div class="fb-section__inner"></div></header>$#',
			$html
		);
		self::assertSame( 1, preg_match( '#fb-section (fb-[0-9a-z]{7})#', $html, $match ) );
		$instance = $match[1] ?? '';
		self::assertSame( "@layer fb.instances{:where(.{$instance}){padding-top:8px;}}", $this->collector->css() );
	}

	public function testIdenticalInstancesAreCompiledOnce(): void
	{
		$attributes = [ 'fb' => [ 'base' => [ 'margin' => [ 'desktop' => [ 'left' => [ 'kind' => 'auto' ] ] ] ] ] ];

		$this->renderer->render( new Section(), $attributes, '' );
		$this->renderer->render( new Section(), $attributes, '' );

		self::assertSame( 1, substr_count( $this->collector->css(), 'margin-left:auto' ) );
	}

	public function testInvalidTagFallsBackToSection(): void
	{
		self::assertStringStartsWith(
			'<section class="fb-section">',
			$this->renderer->render( new Section(), [ 'fb' => [ 'base' => [ 'tag' => 'script' ] ] ], '' )
		);
	}
}
