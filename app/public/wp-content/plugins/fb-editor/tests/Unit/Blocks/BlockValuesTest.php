<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\States\BlockState;
use Fb\Blocks\Values\BlockStyles;
use Fb\Blocks\Values\InstanceClass;
use Fb\Blocks\Values\StatefulValues;
use Fb\Breakpoints\BreakpointList;
use Fb\Css\CssLayer;
use Fb\Styles\Selector;
use Fb\Styles\StyleCompiler;
use Fb\Tests\Fixtures\SectionFixture;
use Fb\Tests\Fixtures\TestBreakpoints;
use PHPUnit\Framework\TestCase;

/**
 * Attribute JSON in → sanitized values, instance class and CSS out.
 * Breakpoints: desktop (base) > tablet ≤1024 > mobile ≤767.
 */
final class BlockValuesTest extends TestCase
{
	private BreakpointList $breakpoints;

	protected function setUp(): void
	{
		$this->breakpoints = TestBreakpoints::list();
	}

	private function values( mixed $attribute ): StatefulValues
	{
		return StatefulValues::fromJson( $attribute, SectionFixture::definition(), $this->breakpoints );
	}

	/**
	 * @param array<string, mixed> $base
	 */
	private function base( array $base ): StatefulValues
	{
		return $this->values( [ 'base' => $base ] );
	}

	private function css( StatefulValues $values, StatefulValues ...$weaker ): string
	{
		$root = Selector::forClass( (string) InstanceClass::of( $values ) );

		return ( new BlockStyles( $this->breakpoints ) )
			->forSource( SectionFixture::definition(), $values, $root, ...$weaker )
			->css( new StyleCompiler( $this->breakpoints ), CssLayer::Instances );
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
		$values = $this->base(
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
			'{"base":{"tag":"div","display":{"desktop":"flex"},"padding":{"desktop":{"top":{"kind":"custom","value":10.0,"unit":"px"}}}}}',
			wp_json_encode( $values, JSON_PRESERVE_ZERO_FRACTION )
		);
	}

	public function testSanitizesStates(): void
	{
		$values = $this->values(
			[
				'states' => [
					'active' => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 2 ) ] ] ],
					'focus'  => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 3 ) ] ] ], // not supported
					'hover'  => [
						'tag'     => 'div',                                                     // static: never per state
						'display' => [ 'desktop' => 'grid' ],
					],
					'nope'   => [ 'display' => [ 'desktop' => 'grid' ] ],
				],
			]
		);

		// Cascade order (hover before active), whatever the stored order.
		self::assertSame(
			'{"states":{"hover":{"display":{"desktop":"grid"}},"active":{"padding":{"desktop":{"top":{"kind":"custom","value":2,"unit":"px"}}}}}}',
			wp_json_encode( $values )
		);
		self::assertTrue( $values->state( BlockState::Focus )->isEmpty() );
	}

	public function testGarbageAttributeYieldsNoValues(): void
	{
		self::assertTrue( $this->values( 'nope' )->isEmpty() );
		self::assertTrue( $this->values( null )->isEmpty() );
		self::assertTrue( $this->values( [ 'base' => 'nope', 'states' => [ 'hover' => 'nope' ] ] )->isEmpty() );
		self::assertSame( '{}', wp_json_encode( $this->base( [] ) ) );
	}

	public function testInstanceClassDependsOnlyOnSanitizedValues(): void
	{
		$a = InstanceClass::of( $this->base( [ 'tag' => 'div', 'padding' => [ 'desktop' => [ 'top' => self::px( 10 ) ] ] ] ) );
		// Different key order + junk that gets dropped: same canonical values.
		$b = InstanceClass::of( $this->base( [ 'junk' => 1, 'padding' => [ 'desktop' => [ 'top' => self::px( 10 ) ] ], 'tag' => 'div' ] ) );
		$c = InstanceClass::of( $this->base( [ 'tag' => 'div', 'padding' => [ 'desktop' => [ 'top' => self::px( 11 ) ] ] ] ) );
		$d = InstanceClass::of( $this->values( [ 'base' => [ 'tag' => 'div' ], 'states' => [ 'hover' => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 10 ) ] ] ] ] ] ) );

		self::assertMatchesRegularExpression( '/^fb-[0-9a-z]{7}$/', (string) $a );
		self::assertSame( (string) $a, (string) $b );
		self::assertNotSame( (string) $a, (string) $c );
		self::assertNotSame( (string) $a, (string) $d );
	}

	public function testConditionHidesGapWhereDisplayIsNotFlexOrGrid(): void
	{
		$values = $this->base(
			[
				'display' => [ 'desktop' => 'flex', 'mobile' => 'block' ],
				'gap'     => [ 'desktop' => [ 'top' => self::px( 16 ), 'left' => self::px( 24 ) ] ],
				'padding' => [ 'desktop' => [ 'top' => self::px( 40 ), 'bottom' => self::px( 40 ) ], 'mobile' => [ 'top' => self::px( 20 ) ] ],
			]
		);
		$class = (string) InstanceClass::of( $values );

		self::assertSame(
			'@layer fb.instances{'
			. ":where(.{$class}>.fb-section__inner){display:flex;row-gap:16px;column-gap:24px;}"
			. ":where(.{$class}){padding-top:40px;padding-bottom:40px;}"
			. '@media (max-width: 767px){'
			. ":where(.{$class}>.fb-section__inner){display:block;row-gap:revert-layer;column-gap:revert-layer;}"
			. ":where(.{$class}){padding-top:20px;}"
			. '}}',
			$this->css( $values )
		);
	}

	public function testStatesGoInTheStateLayerInCascadeOrder(): void
	{
		$values = $this->values(
			[
				'base'   => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 8 ) ] ] ],
				'states' => [
					'active' => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 6 ) ] ] ],
					'hover'  => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 12 ) ], 'mobile' => [ 'top' => self::px( 10 ) ] ] ],
				],
			]
		);
		$class = (string) InstanceClass::of( $values );

		self::assertSame(
			"@layer fb.instances{:where(.{$class}){padding-top:8px;}}"
			. '@layer fb.states.instances{'
			. ":where(.{$class}:hover){padding-top:12px;}"
			. "@media (max-width: 767px){:where(.{$class}:hover){padding-top:10px;}}"
			. ":where(.{$class}:active){padding-top:6px;}"
			. '}',
			$this->css( $values )
		);
	}

	public function testStateConditionsReadStateThenBaseThenWeakerSources(): void
	{
		// The block's gap shows while hovered only where display (hover
		// first, then base, then the default) is flex or grid.
		$values   = $this->values(
			[
				'base'   => [ 'display' => [ 'tablet' => 'block' ] ],
				'states' => [
					'hover' => [
						'display' => [ 'mobile' => 'grid' ],
						'gap'     => [ 'desktop' => [ 'top' => self::px( 4 ) ] ],
					],
				],
			]
		);
		$defaults = $this->base( [ 'display' => [ 'desktop' => 'flex' ] ] );
		$class    = (string) InstanceClass::of( $values );

		self::assertSame(
			"@layer fb.instances{@media (max-width: 1024px){:where(.{$class}>.fb-section__inner){display:block;}}}"
			. '@layer fb.states.instances{'
			. ":where(.{$class}:hover>.fb-section__inner){row-gap:4px;}"
			. "@media (max-width: 1024px){:where(.{$class}:hover>.fb-section__inner){row-gap:revert-layer;}}"
			. "@media (max-width: 767px){:where(.{$class}:hover>.fb-section__inner){display:grid;row-gap:4px;}}"
			. '}',
			$this->css( $values, $defaults )
		);
	}

	public function testStaticControlsWithoutOutputProduceNoCss(): void
	{
		self::assertSame( '', $this->css( $this->base( [ 'tag' => 'section' ] ) ) );
	}
}
