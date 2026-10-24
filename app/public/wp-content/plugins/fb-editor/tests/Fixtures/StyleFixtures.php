<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Library\Section\Section;
use Fb\Blocks\Values\BlockStyles;
use Fb\Blocks\Values\BlockValues;
use Fb\Breakpoints\BreakpointConfig;
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
		$breakpoints = BreakpointConfig::fallback();
		$compiler    = new StyleCompiler( $breakpoints );
		$styles      = new BlockStyles( $breakpoints );

		$cases = [];
		foreach ( self::cases() as $case ) {
			$values  = BlockValues::fromAttribute( $case->attribute, $case->definition, $breakpoints );
			$cases[] = [
				'name'       => $case->name,
				'definition' => $case->definition,
				'attribute'  => $case->attribute,
				'rootClass'  => StyleCase::ROOT_CLASS,
				'values'     => $values,
				'css'        => $compiler->compile( CssLayer::Instances, ...$styles->bindings( $case->definition, $values, Selector::forClass( StyleCase::ROOT_CLASS ) ) ),
			];
		}

		// Same JSON shape as the editor settings' presets.
		$presets = array_map(
			static fn ( string $slug ): Preset => new Preset( PresetKind::Spacing, $slug, $slug, '1rem' ),
			self::SPACING_SLUGS
		);

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
