<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Values;

use Fb\Breakpoints\BreakpointId;
use Fb\Json\JsonValue;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Values\BoxValue;
use Fb\Values\CustomLength;
use Fb\Values\Responsive;
use Fb\Values\Unit;
use PHPUnit\Framework\TestCase;

final class ResponsiveTest extends TestCase
{
	public function testBoxInheritsUnsetSidesFromLargerBreakpoints(): void
	{
		$breakpoints = TestBreakpoints::list();
		$px          = static fn ( int $n ): CustomLength => new CustomLength( $n, Unit::Px );

		$padding = Responsive::base( $breakpoints, BoxValue::all( $px( 80 ) ) )
			->with( new BreakpointId( 'mobile' ), new BoxValue( top: $px( 20 ) ) );

		self::assertEquals( BoxValue::all( $px( 80 ) ), $padding->resolve( new BreakpointId( 'tablet' ), $breakpoints ) );
		self::assertEquals(
			new BoxValue( $px( 20 ), $px( 80 ), $px( 80 ), $px( 80 ) ),
			$padding->resolve( new BreakpointId( 'mobile' ), $breakpoints )
		);
	}

	public function testUnsetEverywhereResolvesToNull(): void
	{
		$breakpoints = TestBreakpoints::list();
		$value       = Responsive::empty()->with( new BreakpointId( 'mobile' ), BoxValue::all( new CustomLength( 1, Unit::Rem ) ) );

		self::assertNull( $value->resolve( new BreakpointId( 'desktop' ), $breakpoints ) );
		self::assertNull( $value->resolve( new BreakpointId( 'tablet' ), $breakpoints ) );
		self::assertNotNull( $value->resolve( new BreakpointId( 'mobile' ), $breakpoints ) );
	}

	public function testParsingDropsUnknownBreakpointsAndInvalidValues(): void
	{
		$breakpoints = TestBreakpoints::list();
		$json        = JsonValue::of(
			[
				'desktop' => [ 'top' => [ 'kind' => 'custom', 'value' => 10, 'unit' => 'px' ] ],
				'tablet'  => [ 'top' => [ 'kind' => 'custom', 'value' => 10, 'unit' => 'nope' ] ],
				'gone'    => [ 'top' => [ 'kind' => 'auto' ] ],
				'Bad Id'  => [ 'top' => [ 'kind' => 'auto' ] ],
			],
			'attrs.fb.padding'
		);

		$value = Responsive::fromJson( $json, BoxValue::fromJson( ... ), $breakpoints );

		self::assertSame(
			'{"desktop":{"top":{"kind":"custom","value":10.0,"unit":"px"}}}',
			wp_json_encode( $value, JSON_PRESERVE_ZERO_FRACTION )
		);
	}

	public function testEmptySerializesAsObject(): void
	{
		self::assertSame( '{}', wp_json_encode( Responsive::empty() ) );
	}
}
