<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\Definition\Input\Breakpoints\BreakpointsInput;
use Fb\Blocks\Hiding\Hiding;
use Fb\Blocks\Library\Container\Container;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Tests\Fixtures\TestRendering;
use Fb\Tests\Fixtures\TestLooks;
use PHPUnit\Framework\TestCase;

/**
 * "Hide on": one class per hidden breakpoint on the root, one fixed
 * stylesheet hiding each class in exactly its breakpoint's range.
 */
final class HidingTest extends TestCase
{
	private TestRendering $rendering;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		$this->rendering = new TestRendering( new BreakpointConfig() );
	}

	protected function tearDown(): void
	{
		remove_all_filters( 'fb_breakpoints' );
	}

	public function testEachBreakpointIsHiddenInItsOwnRangeInTheLastLayer(): void
	{
		self::assertSame(
			'@layer fb.visibility{'
				. '@media (min-width: 1025px){:where(.fb-hide-desktop){display:none;}}'
				. '@media (min-width: 768px) and (max-width: 1024px){:where(.fb-hide-tablet){display:none;}}'
				. '@media (max-width: 767px){:where(.fb-hide-mobile){display:none;}}'
				. '}',
			Hiding::css( TestBreakpoints::list(), editor: false )
		);
	}

	public function testTheEditorAlsoGetsTheFadedLook(): void
	{
		self::assertStringEndsWith( ':where(.fb-hidden-here){opacity:0.35;}}', Hiding::css( TestBreakpoints::list(), editor: true ) );
	}

	public function testRootGetsOneClassPerHiddenBreakpointInBreakpointOrder(): void
	{
		$html = $this->rendering->render( new Container(), [ 'fb' => TestLooks::of( [ 'hideOn' => [ 'mobile', 'gone', 'desktop' ] ] ) ], '' );

		// Unknown breakpoints are kept in the value but give no class.
		self::assertSame( '<div class="fb-container fb-hide-desktop fb-hide-mobile"></div>', $html );
		// Hiding is not instance CSS.
		self::assertSame( '', $this->rendering->collector->css() );
	}

	public function testAnEmptyPickIsAValue(): void
	{
		self::assertSame( [], ( new BreakpointsInput() )->parse( JsonValue::of( [], 'hideOn' ) )->jsonSerialize() );
	}

	public function testRejectsInvalidIds(): void
	{
		$this->expectException( InvalidJson::class );
		( new BreakpointsInput() )->parse( JsonValue::of( [ 'Mobile!' ], 'hideOn' ) );
	}
}
