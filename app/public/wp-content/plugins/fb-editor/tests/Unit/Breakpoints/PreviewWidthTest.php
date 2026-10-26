<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Breakpoints;

use Fb\Breakpoints\Breakpoint;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Breakpoints\BreakpointId;
use Fb\Breakpoints\BreakpointList;
use PHPUnit\Framework\TestCase;

/**
 * Every breakpoint previews at a fixed width inside its own range, so the
 * editor window's size never decides which media queries apply.
 */
final class PreviewWidthTest extends TestCase
{
	public function testBootstrapBreakpointsPreviewAtTheirSmallestWidth(): void
	{
		$list   = BreakpointConfig::fallback();
		$widths = [];
		foreach ( $list as $breakpoint ) {
			$widths[ $breakpoint->id->value ] = $list->previewWidth( $breakpoint );
		}

		self::assertSame( [ 'xxl' => 1400, 'xl' => 1200, 'lg' => 992, 'md' => 768, 'sm' => 576, 'xs' => 375 ], $widths );
	}

	public function testExplicitPreviewWidthWins(): void
	{
		$list = new BreakpointList( new Breakpoint( new BreakpointId( 'desktop' ), 'D', previewWidth: 1600 ), new Breakpoint( new BreakpointId( 'mobile' ), 'M', 767, 390 ) );

		self::assertSame( 1600, $list->previewWidth( $list->base ) );
		self::assertSame( '[1600,390]', (string) wp_json_encode( array_column( $list->jsonSerialize(), 'previewWidth' ) ) );
	}

	public function testPreviewWidthOutsideTheRangeIsRejected(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'outside its own range' );

		// 900px would show the desktop's media queries, not the mobile ones.
		new BreakpointList( new Breakpoint( new BreakpointId( 'desktop' ), 'D' ), new Breakpoint( new BreakpointId( 'mobile' ), 'M', 767, 900 ) );
	}

	public function testATinySmallestBreakpointIsCapped(): void
	{
		$list = new BreakpointList( new Breakpoint( new BreakpointId( 'desktop' ), 'D' ), new Breakpoint( new BreakpointId( 'watch' ), 'W', 320 ) );

		self::assertSame( [ 321, 320 ], array_map( $list->previewWidth( ... ), iterator_to_array( $list, false ) ) );
	}
}
