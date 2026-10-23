<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Values;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Presets\PresetKind;
use Fb\Values\AutoLength;
use Fb\Values\CssNumber;
use Fb\Values\CustomLength;
use Fb\Values\Lengths;
use Fb\Values\PresetLength;
use Fb\Values\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LengthTest extends TestCase
{
	/**
	 * @return iterable<string, array{float, string}>
	 */
	public static function numbers(): iterable
	{
		yield 'integer'        => [ 16.0, '16' ];
		yield 'decimal'        => [ 1.5, '1.5' ];
		yield 'trailing zeros' => [ 2.50, '2.5' ];
		yield 'negative'       => [ -12.25, '-12.25' ];
		yield 'tiny'           => [ 0.00001, '0' ];
		yield 'negative zero'  => [ -0.0, '0' ];
		yield 'no exponent'    => [ 1.0e-4, '0.0001' ];
	}

	#[DataProvider( 'numbers' )]
	public function testCssNumberFormatting( float $number, string $expected ): void
	{
		self::assertSame( $expected, CssNumber::format( $number ) );
	}

	public function testCustomLengthCss(): void
	{
		self::assertSame( '1.5rem', ( new CustomLength( 1.5, Unit::Rem ) )->toCss() );
		self::assertSame( '-8px', ( new CustomLength( -8, Unit::Px ) )->toCss() );
		self::assertSame( '0', ( new CustomLength( 0, Unit::Percent ) )->toCss() );
		self::assertSame( '50%', ( new CustomLength( 50, Unit::Percent ) )->toCss() );
	}

	public function testPresetLengthReferencesCoreVariable(): void
	{
		self::assertSame( 'var(--wp--preset--spacing--40)', ( new PresetLength( PresetKind::Spacing, '40' ) )->toCss() );
	}

	public function testRoundTripThroughJson(): void
	{
		foreach ( [ new PresetLength( PresetKind::Spacing, '80' ), new CustomLength( -12.5, Unit::Px ), new AutoLength() ] as $length ) {
			$json = JsonValue::parse( (string) wp_json_encode( $length ), 'test' );
			self::assertEquals( $length, Lengths::fromJson( $json ) );
		}
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function invalidJson(): iterable
	{
		yield 'unknown kind'      => [ '{"kind":"calc","value":"1px + 2px"}' ];
		yield 'unknown unit'      => [ '{"kind":"custom","value":1,"unit":"pt;color:red"}' ];
		yield 'string value'      => [ '{"kind":"custom","value":"10","unit":"px"}' ];
		yield 'out of range'      => [ '{"kind":"custom","value":1e9,"unit":"px"}' ];
		yield 'injection in slug' => [ '{"kind":"preset","preset":"spacing","slug":"40);color:red"}' ];
		yield 'unknown preset'    => [ '{"kind":"preset","preset":"evil","slug":"40"}' ];
		yield 'missing kind'      => [ '{"value":1,"unit":"px"}' ];
	}

	#[DataProvider( 'invalidJson' )]
	public function testRejectsInvalidJson( string $json ): void
	{
		$this->expectException( InvalidJson::class );
		Lengths::fromJson( JsonValue::parse( $json, 'test' ) );
	}
}
