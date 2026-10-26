<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Library\Section\Section;
use Fb\CoreBlocks\Library\Button\Button;
use Fb\Blocks\Values\BlockStyles;
use Fb\Blocks\Values\StatefulValues;
use Fb\Css\CssLayer;
use Fb\Presets\Preset;
use Fb\Presets\PresetKind;
use Fb\Styles\Selector;
use Fb\Styles\StyleCompiler;

/**
 * Builds tests/fixtures/styles.json: every case's definition (as exported
 * to the editor), raw attribute, and the CSS PHP compiles from it.
 *
 * The JS suite must produce byte-identical CSS from the same input.
 */
final class StyleFixtures
{
	public const string FILE = __DIR__ . '/styles.json';

	/** Spacing slugs the cases use, including ones that exercise kebab-casing. */
	private const array SPACING_SLUGS = [ '10', '40', '50', '80', '2xl', 'xLarge', 'x_small' ];

	public static function json(): string
	{
		$breakpoints = TestBreakpoints::list();
		$compiler    = new StyleCompiler( $breakpoints );
		$styles      = new BlockStyles( $breakpoints );

		$cases = [];
		foreach ( self::cases() as $case ) {
			$values   = StatefulValues::fromJson( $case->attribute(), $case->definition, $breakpoints );
			$defaults = StatefulValues::fromJson( $case->defaultsAttribute(), $case->definition, $breakpoints );
			$variant  = null === $case->variant ? null : StatefulValues::fromJson( $case->variant, $case->definition, $breakpoints );
			$weaker   = null === $variant ? [ $defaults ] : [ $variant, $defaults ];
			$cases[]  = [
				'name'        => $case->name,
				'definition'  => $case->definition,
				'attribute'   => $case->attribute(),
				'defaults'    => $case->defaultsAttribute(),
				'rootClass'   => StyleCase::ROOT_CLASS,
				'variantClass' => StyleCase::VARIANT_CLASS,
				'values'      => $values,
				'variant'     => null === $case->variant ? null : (object) $case->variant,
				'defaultsCss' => $styles->forSource( $case->definition, $defaults, Selector::forClass( $case->definition->name->cssClass() ) )->css( $compiler, CssLayer::Defaults ),
				'variantCss'  => null === $variant ? '' : $styles->forSource( $case->definition, $variant, Selector::forClass( StyleCase::VARIANT_CLASS ), $defaults )->css( $compiler, CssLayer::Variants ),
				'css'         => $styles->forSource( $case->definition, $values, Selector::forClass( StyleCase::ROOT_CLASS ), ...$weaker )->css( $compiler, CssLayer::Instances ),
			];
		}

		// Same JSON shape as the editor settings' presets.
		$presets = [
			...array_map(
				static fn ( string $slug ): Preset => new Preset( PresetKind::Spacing, $slug, $slug, '1rem' ),
				self::SPACING_SLUGS
			),
			new Preset( PresetKind::Color, 'accent', 'Accent', '#3858e9' ),
			new Preset( PresetKind::Color, 'x-dark', 'Extra dark', '#111111' ),
		];

		return (string) wp_json_encode(
			[ 'breakpoints' => $breakpoints, 'presets' => $presets, 'cases' => $cases ],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
		) . "\n";
	}

	/**
	 * @return list<StyleCase>
	 */
	private static function cases(): array
	{
		$fixture = SectionFixture::definition();
		$section = ( new Section() )->definition();
		$button  = ( new Button() )->definition();

		return [
			new StyleCase( 'padding with presets and a mobile override', $section, [
				'padding' => [
					'desktop' => [ 'top' => self::preset( '80' ), 'bottom' => self::preset( '80' ) ],
					'mobile'  => [ 'top' => self::preset( '50' ) ],
				],
			] ),
			new StyleCase( 'tablet-only value', $section, [
				'margin' => [ 'tablet' => [ 'left' => [ 'kind' => 'auto' ], 'right' => [ 'kind' => 'auto' ] ] ],
			] ),
			new StyleCase( 'repeated values are not written twice', $section, [
				'padding' => [
					'desktop' => [ 'top' => self::px( 10 ), 'left' => self::px( 10 ) ],
					'tablet'  => [ 'top' => self::px( 10 ), 'left' => self::px( 12 ) ],
					'mobile'  => [ 'top' => self::px( 10 ) ],
				],
			] ),
			new StyleCase( 'gap hidden where display is block', $fixture, [
				'display' => [ 'desktop' => 'flex', 'mobile' => 'block' ],
				'gap'     => [ 'desktop' => [ 'top' => self::px( 16 ), 'left' => self::rem( 1.5 ) ] ],
			] ),
			new StyleCase( 'gap hidden on tablet then visible again', $fixture, [
				'display' => [ 'desktop' => 'grid', 'tablet' => 'block', 'mobile' => 'flex' ],
				'gap'     => [ 'desktop' => [ 'top' => self::preset( '40' ), 'left' => self::preset( '40' ) ] ],
			] ),
			new StyleCase( 'gap never visible writes nothing', $fixture, [
				'gap' => [ 'desktop' => [ 'top' => self::px( 8 ) ] ],
			] ),
			new StyleCase( 'number formatting and negative margins', $section, [
				'margin' => [
					'desktop' => [
						'top'    => [ 'kind' => 'custom', 'value' => -12.5, 'unit' => 'px' ],
						'right'  => [ 'kind' => 'custom', 'value' => 33.33333, 'unit' => '%' ],
						'bottom' => [ 'kind' => 'custom', 'value' => 0, 'unit' => 'rem' ],
						'left'   => [ 'kind' => 'custom', 'value' => 1.25, 'unit' => 'vw' ],
					],
					'mobile'  => [ 'bottom' => [ 'kind' => 'custom', 'value' => -0.0, 'unit' => 'em' ] ],
				],
			] ),
			new StyleCase( 'kebab-cased preset slugs', $section, [
				'padding' => [ 'desktop' => [ 'top' => self::preset( '2xl' ), 'right' => self::preset( 'xLarge' ), 'bottom' => self::preset( 'x_small' ) ] ],
			] ),
			new StyleCase( 'invalid values are dropped one by one', $section, [
				'padding' => [
					'desktop' => [ 'top' => self::px( -4 ), 'left' => self::px( 4 ) ],
					'tablet'  => [ 'top' => [ 'kind' => 'auto' ] ],
					'mobile'  => [ 'top' => [ 'kind' => 'custom', 'value' => 4, 'unit' => 'pt' ] ],
					'phone'   => [ 'top' => self::px( 1 ) ],
				],
				'display' => [ 'desktop' => 'table' ],
				'unknown' => [ 'desktop' => 'x' ],
			] ),
			new StyleCase( 'static control without output', $section, [ 'tag' => 'header' ] ),
			new StyleCase( 'palette and custom colors', $section, [
				'background' => [ 'desktop' => self::color( 'accent' ), 'mobile' => [ 'kind' => 'custom', 'value' => '#FFAA00' ] ],
				'textColor'  => [ 'desktop' => self::color( 'x-dark' ), 'tablet' => self::color( 'x-dark' ) ],
			] ),
			new StyleCase( 'invalid colors are dropped', $section, [
				'background' => [
					'desktop' => [ 'kind' => 'custom', 'value' => 'red;}body{' ],
					'tablet'  => [ 'kind' => 'preset', 'preset' => 'spacing', 'slug' => '40' ],
					'mobile'  => [ 'kind' => 'custom', 'value' => '#abc' ],
				],
			] ),
			new StyleCase( 'single lengths', $section, [
				'minHeight' => [ 'desktop' => [ 'kind' => 'custom', 'value' => 100, 'unit' => 'vh' ], 'mobile' => [ 'kind' => 'auto' ] ],
				'maxWidth'  => [ 'desktop' => [ 'kind' => 'custom', 'value' => 72.5, 'unit' => 'rem' ], 'tablet' => self::preset( '40' ) ],
			] ),
			new StyleCase( 'wrap toggle only while display is flex', $section, [
				'display' => [ 'desktop' => 'flex', 'mobile' => 'grid' ],
				'wrap'    => [ 'desktop' => true, 'tablet' => false ],
			] ),
			new StyleCase( 'toggle rejects non-booleans', $section, [
				'display' => [ 'desktop' => 'flex' ],
				'wrap'    => [ 'desktop' => 'yes', 'tablet' => 1, 'mobile' => false ],
			] ),
			new StyleCase(
				'type defaults with a breakpoint override',
				$section,
				[],
				[ 'padding' => [ 'desktop' => [ 'top' => self::preset( '80' ) ], 'mobile' => [ 'top' => self::preset( '40' ) ] ] ]
			),
			new StyleCase(
				'instance condition reads the default',
				$fixture,
				[ 'gap' => [ 'desktop' => [ 'top' => self::px( 12 ), 'left' => self::px( 12 ) ] ] ],
				[ 'display' => [ 'desktop' => 'flex', 'mobile' => 'block' ] ]
			),
			new StyleCase(
				'instance value beats the default condition',
				$fixture,
				[ 'display' => [ 'mobile' => 'grid' ], 'gap' => [ 'desktop' => [ 'top' => self::px( 12 ) ] ] ],
				[ 'display' => [ 'desktop' => 'flex', 'mobile' => 'block' ] ]
			),
			new StyleCase(
				'core block styling a child element',
				$button,
				[
					'background' => [ 'desktop' => self::color( 'accent' ) ],
					'fontWeight' => [ 'desktop' => '700', 'mobile' => '400' ],
					'fontSize'   => [ 'desktop' => [ 'kind' => 'custom', 'value' => 1.125, 'unit' => 'rem' ] ],
				],
				[ 'padding' => [ 'desktop' => [ 'top' => self::preset( '40' ), 'bottom' => self::preset( '40' ) ] ] ]
			),
			new StyleCase(
				'states over the base look, in cascade order',
				$button,
				[ 'background' => [ 'desktop' => self::color( 'accent' ) ] ],
				[ 'fontWeight' => [ 'desktop' => '400' ] ],
				[
					'active' => [ 'background' => [ 'desktop' => self::color( 'x-dark' ) ] ],
					'hover'  => [ 'background' => [ 'desktop' => [ 'kind' => 'custom', 'value' => '#222222' ] ], 'fontWeight' => [ 'mobile' => '700' ] ],
					'focus'  => [ 'padding' => [ 'tablet' => [ 'top' => self::px( 2 ) ] ] ],
				],
				[ 'hover' => [ 'fontWeight' => [ 'desktop' => '600' ] ] ]
			),
			new StyleCase(
				'state conditions read state, then base, then defaults',
				$fixture,
				[ 'display' => [ 'tablet' => 'block' ] ],
				[ 'display' => [ 'desktop' => 'flex' ] ],
				[
					'hover'  => [ 'display' => [ 'mobile' => 'grid' ], 'gap' => [ 'desktop' => [ 'top' => self::px( 4 ) ] ] ],
					'focus'  => [ 'display' => [ 'desktop' => 'grid' ] ],
					'active' => [ 'tag' => 'div', 'gap' => [ 'tablet' => [ 'left' => self::px( 9 ) ] ] ],
				],
				[ 'active' => [ 'display' => [ 'tablet' => 'grid' ] ] ]
			),
			new StyleCase(
				'variant between the defaults and the block',
				$fixture,
				[ 'gap' => [ 'desktop' => [ 'top' => self::px( 8 ) ] ] ],
				[ 'display' => [ 'desktop' => 'flex' ] ],
				[ 'hover' => [ 'gap' => [ 'desktop' => [ 'top' => self::px( 2 ) ] ] ] ],
				[],
				[
					'base'   => [ 'display' => [ 'tablet' => 'block' ], 'padding' => [ 'desktop' => [ 'top' => self::px( 20 ) ] ] ],
					'states' => [ 'hover' => [ 'display' => [ 'desktop' => 'grid' ] ], 'focus' => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 1 ) ] ] ] ],
				]
			),
			new StyleCase( 'garbage states', $section, [], [], [ 'hover' => 'nope', 'focus' => [ 'padding' => 'x' ] ] ),
			new StyleCase( 'garbage attribute', $section, [ 'padding' => 'nope', 'margin' => [ 'desktop' => 'nope' ] ] ),
		];
	}

	/**
	 * @return array{kind: 'preset', preset: 'spacing', slug: string}
	 */
	private static function preset( string $slug ): array
	{
		return [ 'kind' => 'preset', 'preset' => 'spacing', 'slug' => $slug ];
	}

	/**
	 * @return array{kind: 'preset', preset: 'color', slug: string}
	 */
	private static function color( string $slug ): array
	{
		return [ 'kind' => 'preset', 'preset' => 'color', 'slug' => $slug ];
	}

	/**
	 * @return array{kind: 'custom', value: int, unit: 'px'}
	 */
	private static function px( int $value ): array
	{
		return [ 'kind' => 'custom', 'value' => $value, 'unit' => 'px' ];
	}

	/**
	 * @return array{kind: 'custom', value: float, unit: 'rem'}
	 */
	private static function rem( float $value ): array
	{
		return [ 'kind' => 'custom', 'value' => $value, 'unit' => 'rem' ];
	}
}
