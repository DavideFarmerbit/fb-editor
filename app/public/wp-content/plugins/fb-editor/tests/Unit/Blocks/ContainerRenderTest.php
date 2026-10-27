<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\BaseStyles\BaseStylesheet;
use Fb\Blocks\Library\Container\Container;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Tests\Fixtures\TestRendering;
use PHPUnit\Framework\TestCase;

/**
 * Raw attributes in → markup and page CSS out, as on the frontend.
 */
final class ContainerRenderTest extends TestCase
{
	private InstanceStyleCollector $collector;
	private TestRendering $rendering;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		$this->rendering = new TestRendering( new BreakpointConfig() );
		$this->collector = $this->rendering->collector;
	}

	protected function tearDown(): void
	{
		remove_all_filters( 'fb_breakpoints' );
	}

	public function testChildrenAreDirectChildrenOfADiv(): void
	{
		self::assertSame( '<div class="fb-container"><p>x</p></div>', $this->rendering->render( new Container(), [], '<p>x</p>' ) );
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

		$html = $this->rendering->render( new Container(), $attributes, '' );

		self::assertMatchesRegularExpression( '#^<header class="fb-container (fb-[0-9a-z]{7}) hero &quot;&gt;&lt;script&gt;"></header>$#', $html );
		self::assertSame( 1, preg_match( '#fb-container (fb-[0-9a-z]{7})#', $html, $match ) );
		$instance = $match[1] ?? '';
		// Padding goes through custom properties, composed by the base CSS.
		self::assertSame( "@layer fb.instances{:where(.{$instance}){--fb-padding-top:8px;}}", $this->collector->css() );
	}

	public function testIdenticalInstancesAreCompiledOnce(): void
	{
		$attributes = [ 'fb' => [ 'base' => [ 'margin' => [ 'desktop' => [ 'left' => [ 'kind' => 'auto' ] ] ] ] ] ];

		$this->rendering->render( new Container(), $attributes, '' );
		$this->rendering->render( new Container(), $attributes, '' );

		self::assertSame( 1, substr_count( $this->collector->css(), 'margin-left:auto' ) );
	}

	public function testInvalidTagFallsBackToDiv(): void
	{
		self::assertStringStartsWith( '<div class="fb-container">', $this->rendering->render( new Container(), [ 'fb' => [ 'base' => [ 'tag' => 'script' ] ] ], '' ) );
	}

	public function testBaseCssComposesPaddingWithTheContentWidth(): void
	{
		self::assertSame(
			'@layer fb.base{:where(.fb-container){'
			. '--fb-padding-top:0px;--fb-padding-right:0px;--fb-padding-bottom:0px;--fb-padding-left:0px;--fb-content-width:100%;'
			. 'padding-top:var(--fb-padding-top);padding-bottom:var(--fb-padding-bottom);'
			. 'padding-left:max(var(--fb-padding-left),calc((100% - var(--fb-content-width))/2));'
			. 'padding-right:max(var(--fb-padding-right),calc((100% - var(--fb-content-width))/2));'
			. 'position:relative;isolation:isolate;}'
			. ':where(.fb-container>.fb-container__media){position:absolute;top:0;right:0;bottom:0;left:0;width:100%;height:100%;z-index:-1;pointer-events:none;object-fit:cover;}'
			. ':where(.fb-container>.fb-container__overlay){position:absolute;top:0;right:0;bottom:0;left:0;width:100%;height:100%;z-index:-1;pointer-events:none;}}',
			( new BaseStylesheet() )->css( [ new Container() ], false )
		);
	}

	public function testGridColumnsAndContentWidth(): void
	{
		$html = $this->rendering->render(
			new Container(),
			[
				'fb' => [
					'base' => [
						'display'      => [ 'desktop' => 'grid' ],
						'columns'      => [ 'desktop' => [ 'kind' => 'count', 'count' => 3 ], 'mobile' => [ 'kind' => 'fit', 'min' => [ 'kind' => 'custom', 'value' => 240, 'unit' => 'px' ] ] ],
						'contentWidth' => [ 'desktop' => [ 'kind' => 'custom', 'value' => 72, 'unit' => 'rem' ] ],
					],
				],
			],
			''
		);
		self::assertSame( 1, preg_match( '#fb-container (fb-[0-9a-z]{7})#', $html, $match ) );
		$instance = $match[1] ?? '';

		self::assertSame(
			"@layer fb.instances{:where(.{$instance}){display:grid;grid-template-columns:repeat(3,minmax(0,1fr));--fb-content-width:72rem;}"
			. "@media (max-width: 767px){:where(.{$instance}){grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));}}}",
			$this->collector->css()
		);
	}
}
