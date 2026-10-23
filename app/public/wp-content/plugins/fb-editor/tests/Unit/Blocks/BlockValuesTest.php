<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\Values\BlockStyles;
use Fb\Blocks\Values\BlockValues;
use Fb\Blocks\Values\InstanceClass;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Breakpoints\BreakpointList;
use Fb\Css\CssLayer;
use Fb\Styles\Selector;
use Fb\Styles\StyleCompiler;
use Fb\Tests\Fixtures\SectionFixture;
use PHPUnit\Framework\TestCase;

/**
 * Attribute JSON in → sanitized values, instance class and CSS out.
 * Breakpoints (fallback): desktop (base) > tablet ≤1024 > mobile ≤767.
 */
final class BlockValuesTest extends TestCase
{
	private BreakpointList $breakpoints;

	protected function setUp(): void
	{
		$this->breakpoints = BreakpointConfig::fallback();
	}

	/**
	 * @param array<string, mixed> $attribute
	 */
	private function values( array $attribute ): BlockValues
	{
		return BlockValues::fromAttribute( $attribute, SectionFixture::definition(), $this->breakpoints );
	}

	/**
	 * @param array<string, mixed> $attribute
	 */
	private function css( array $attribute ): string
	{
		$values = $this->values( $attribute );
		$root   = Selector::forClass( (string) InstanceClass::of( $values ) );

		return ( new StyleCompiler( $this->breakpoints ) )->compile(
			CssLayer::Instances,
			...( new BlockStyles( $this->breakpoints ) )->bindings( SectionFixture::definition(), $values, $root )
		);
	}

	/**
	 * @return array{kind: 'custom', value: int, unit: 'px'}
	 */
	private static function px( int $n ): array
	{
		return [ 'kind' => 'custom', 'value' => $n, 'unit' => 'px' ];
	}

	public function testSanitizesStoredValues(): void
	{
		$values = $this->values(
			[
				'tag'     => 'div',
				'display' => [ 'desktop' => 'flex', 'mobile' => 'table' ],              // "table" isn't an option
				'padding' => [
					'desktop' => [ 'top' => self::px( 10 ) ],
					'tablet'  => [ 'top' => self::px( -5 ) ],                             // negative padding
					'mobile'  => [ 'top' => [ 'kind' => 'auto' ] ],                       // auto padding
				],
				'unknown' => [ 'desktop' => 'x' ],                                        // not a control
			]
		);

		self::assertSame(
			'{"tag":"div","display":{"desktop":"flex"},"padding":{"desktop":{"top":{"kind":"custom","value":10.0,"unit":"px"}}}}',
			wp_json_encode( $values, JSON_PRESERVE_ZERO_FRACTION )
		);
	}

	public function testGarbageAttributeYieldsNoValues(): void
	{
		self::assertTrue( BlockValues::fromAttribute( 'nope', SectionFixture::definition(), $this->breakpoints )->isEmpty() );
		self::assertTrue( BlockValues::fromAttribute( null, SectionFixture::definition(), $this->breakpoints )->isEmpty() );
		self::assertSame( '{}', wp_json_encode( $this->values( [] ) ) );
	}

	public function testInstanceClassDependsOnlyOnSanitizedValues(): void
	{
		$a = InstanceClass::of( $this->values( [ 'tag' => 'div', 'padding' => [ 'desktop' => [ 'top' => self::px( 10 ) ] ] ] ) );
		// Different key order + junk that gets dropped: same canonical values.
		$b = InstanceClass::of( $this->values( [ 'junk' => 1, 'padding' => [ 'desktop' => [ 'top' => self::px( 10 ) ] ], 'tag' => 'div' ] ) );
		$c = InstanceClass::of( $this->values( [ 'tag' => 'div', 'padding' => [ 'desktop' => [ 'top' => self::px( 11 ) ] ] ] ) );

		self::assertMatchesRegularExpression( '/^fb-[0-9a-z]{7}$/', (string) $a );
		self::assertSame( (string) $a, (string) $b );
		self::assertNotSame( (string) $a, (string) $c );
	}

	public function testConditionHidesGapWhereDisplayIsNotFlexOrGrid(): void
	{
		$attribute = [
			'display' => [ 'desktop' => 'flex', 'mobile' => 'block' ],
			'gap'     => [ 'desktop' => [ 'top' => self::px( 16 ), 'left' => self::px( 24 ) ] ],
			'padding' => [ 'desktop' => [ 'top' => self::px( 40 ), 'bottom' => self::px( 40 ) ], 'mobile' => [ 'top' => self::px( 20 ) ] ],
		];
		$class = (string) InstanceClass::of( $this->values( $attribute ) );

		self::assertSame(
			'@layer fb.instances{'
			. ":where(.{$class}>.fb-section__inner){display:flex;row-gap:16px;column-gap:24px;}"
			. ":where(.{$class}){padding-top:40px;padding-bottom:40px;}"
			. '@media (max-width: 767px){'
			. ":where(.{$class}>.fb-section__inner){display:block;row-gap:revert-layer;column-gap:revert-layer;}"
			. ":where(.{$class}){padding-top:20px;}"
			. '}}',
			$this->css( $attribute )
		);
	}

	public function testStaticControlsWithoutOutputProduceNoCss(): void
	{
		self::assertSame( '', $this->css( [ 'tag' => 'section' ] ) );
	}
}
