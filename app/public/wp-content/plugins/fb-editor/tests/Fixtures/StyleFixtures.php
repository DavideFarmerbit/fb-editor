<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Library\Container\Container;
use Fb\Blocks\BlockExtensions;
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
			// Defaults and variants parse like their repositories: no placed values.
			$defaults = StatefulValues::fromJson( $case->defaultsAttribute(), $case->definition, $breakpoints, placed: false );
			$variant  = null === $case->variant ? null : StatefulValues::fromJson( $case->variant, $case->definition, $breakpoints, placed: false );
			$weaker   = null === $variant ? [ $defaults ] : [ $variant, $defaults ];
			$cases[]  = [
				'name'        => $case->name,
				'definition'  => $case->definition,
				'attribute'   => $case->attribute(),
				'defaults'    => $case->defaultsAttribute(),
				'rootClass'   => StyleCase::ROOT_CLASS,
				'variantClass' => StyleCase::VARIANT_CLASS,
				'values'      => $values,
				'variant'     => $case->variant,
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
			new Preset( PresetKind::Shadow, 'md', 'Medium', '0 4px 12px rgba(0,0,0,.1)' ),
			new Preset( PresetKind::Gradient, 'accent-fade', 'Accent fade', 'linear-gradient(#3858e9,#111111)' ),
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
		// Complete definitions (with Container item controls), as the editor gets them.
		$container = BlockExtensions::complete( ( new Container() )->definition() );
		$button  = BlockExtensions::complete( ( new Button() )->definition() );

		return [
			new StyleCase( 'padding with presets and a mobile override', $container, [
				'padding' => [
					'desktop' => [ 'top' => self::preset( '80' ), 'bottom' => self::preset( '80' ) ],
					'mobile'  => [ 'top' => self::preset( '50' ) ],
				],
			] ),
			new StyleCase( 'tablet-only value', $container, [
				'margin' => [ 'tablet' => [ 'left' => [ 'kind' => 'auto' ], 'right' => [ 'kind' => 'auto' ] ] ],
			] ),
			new StyleCase( 'repeated values are not written twice', $container, [
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
			new StyleCase( 'number formatting and negative margins', $container, [
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
			new StyleCase( 'kebab-cased preset slugs', $container, [
				'padding' => [ 'desktop' => [ 'top' => self::preset( '2xl' ), 'right' => self::preset( 'xLarge' ), 'bottom' => self::preset( 'x_small' ) ] ],
			] ),
			new StyleCase( 'invalid values are dropped one by one', $container, [
				'padding' => [
					'desktop' => [ 'top' => self::px( -4 ), 'left' => self::px( 4 ) ],
					'tablet'  => [ 'top' => [ 'kind' => 'auto' ] ],
					'mobile'  => [ 'top' => [ 'kind' => 'custom', 'value' => 4, 'unit' => 'pt' ] ],
					'phone'   => [ 'top' => self::px( 1 ) ],
				],
				'display' => [ 'desktop' => 'table' ],
				'unknown' => [ 'desktop' => 'x' ],
			] ),
			new StyleCase( 'static control without output', $container, [ 'tag' => 'header' ] ),
			new StyleCase( 'palette and custom colors', $container, [
				'background' => [ 'desktop' => self::color( 'accent' ), 'mobile' => [ 'kind' => 'custom', 'value' => '#FFAA00' ] ],
				'textColor'  => [ 'desktop' => self::color( 'x-dark' ), 'tablet' => self::color( 'x-dark' ) ],
			] ),
			new StyleCase( 'invalid colors are dropped', $container, [
				'background' => [
					'desktop' => [ 'kind' => 'custom', 'value' => 'red;}body{' ],
					'tablet'  => [ 'kind' => 'preset', 'preset' => 'spacing', 'slug' => '40' ],
					'mobile'  => [ 'kind' => 'custom', 'value' => '#abc' ],
				],
			] ),
			new StyleCase( 'single lengths', $container, [
				'minHeight' => [ 'desktop' => [ 'kind' => 'custom', 'value' => 100, 'unit' => 'vh' ], 'mobile' => [ 'kind' => 'auto' ] ],
				'maxWidth'  => [ 'desktop' => [ 'kind' => 'custom', 'value' => 72.5, 'unit' => 'rem' ], 'tablet' => self::preset( '40' ) ],
			] ),
			new StyleCase( 'wrap toggle only while display is flex', $container, [
				'display' => [ 'desktop' => 'flex', 'mobile' => 'grid' ],
				'wrap'    => [ 'desktop' => true, 'tablet' => false ],
			] ),
			new StyleCase( 'toggle rejects non-booleans', $container, [
				'display' => [ 'desktop' => 'flex' ],
				'wrap'    => [ 'desktop' => 'yes', 'tablet' => 1, 'mobile' => false ],
			] ),
			new StyleCase(
				'type defaults with a breakpoint override',
				$container,
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
				StyleCase::looks(
					[ 'display' => [ 'tablet' => 'block' ], 'padding' => [ 'desktop' => [ 'top' => self::px( 20 ) ] ] ],
					[ 'hover' => [ 'display' => [ 'desktop' => 'grid' ] ], 'focus' => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 1 ) ] ] ] ]
				)
			),
			new StyleCase(
				'combined states and media conditions, in cascade order',
				$fixture,
				[ 'display' => [ 'desktop' => 'flex' ] ],
				[ 'padding' => [ 'desktop' => [ 'top' => self::px( 3 ) ] ] ],
				[ 'hover' => [ 'gap' => [ 'desktop' => [ 'top' => self::px( 2 ) ] ] ] ],
				[],
				null,
				[
					// Given out of order: compiled in cascade order.
					[ 'states' => [ 'active', 'hover' ], 'values' => [ 'gap' => [ 'desktop' => [ 'top' => self::px( 6 ) ] ] ] ],
					[ 'states' => [ 'hover' ], 'when' => [ 'dark' ], 'values' => [ 'gap' => [ 'tablet' => [ 'top' => self::px( 5 ) ] ] ] ],
					[ 'when' => [ 'reduced-motion', 'dark' ], 'values' => [ 'display' => [ 'mobile' => 'grid' ] ] ],
					[ 'when' => [ 'dark' ], 'values' => [ 'padding' => [ 'desktop' => [ 'top' => self::px( 7 ) ] ] ] ],
					// Dropped: unknown condition, unsupported state, repeated look.
					[ 'when' => [ 'sunny' ], 'values' => [ 'display' => [ 'desktop' => 'grid' ] ] ],
					[ 'states' => [ 'visited' ], 'values' => [ 'display' => [ 'desktop' => 'grid' ] ] ],
					[ 'when' => [ 'dark' ], 'values' => [ 'display' => [ 'desktop' => 'grid' ] ] ],
				]
			),
			new StyleCase(
				'parts: pseudo-elements, an element, states and conditions on parts',
				$container,
				[ 'display' => [ 'desktop' => 'flex' ] ],
				[],
				[],
				[],
				null,
				[
					[
						'part'   => 'before',
						'values' => [
							'content'  => [ 'desktop' => 'Say "hi" \\ </style><b>', 'mobile' => '' ],
							'width'    => [ 'desktop' => self::px( 40 ) ],
							'position' => [ 'desktop' => 'absolute' ],
							'inset'    => [ 'desktop' => [ 'top' => self::px( 0 ) ] ],
							'display'  => [ 'desktop' => 'block', 'tablet' => 'grid' ],     // grid: not a ::before option, dropped
						],
					],
					[ 'part' => 'before', 'states' => [ 'hover' ], 'values' => [ 'width' => [ 'desktop' => self::px( 80 ) ] ] ],
					[ 'part' => 'after', 'when' => [ 'dark' ], 'values' => [ 'content' => [ 'desktop' => "\u{2192}\x07" ] ] ],
					[ 'part' => 'media', 'states' => [ 'hover' ], 'values' => [ 'objectFit' => [ 'desktop' => 'contain' ], 'opacity' => [ 'tablet' => 0.5 ] ] ],
					[ 'part' => 'nope', 'values' => [ 'width' => [ 'desktop' => self::px( 1 ) ] ] ],   // unknown part: dropped
				]
			),
			new StyleCase( 'grid columns, numbers and spans', $container, [
				'display'        => [ 'desktop' => 'grid', 'mobile' => 'flex' ],
				'columns'        => [
					'desktop' => [ 'kind' => 'count', 'count' => 4 ],
					'tablet'  => [ 'kind' => 'fit', 'min' => [ 'kind' => 'custom', 'value' => 12.5, 'unit' => 'rem' ] ],
					'mobile'  => [ 'kind' => 'count', 'count' => 13 ],
				],
				'contentWidth'   => [ 'desktop' => [ 'kind' => 'custom', 'value' => 1200, 'unit' => 'px' ] ],
				'itemGrow'       => [ 'desktop' => 1.5, 'tablet' => -1 ],
				'itemOrder'      => [ 'desktop' => 2, 'tablet' => 2.5 ],
				'itemColumnSpan' => [ 'desktop' => 3, 'mobile' => 0 ],
				'itemBasis'      => [ 'desktop' => [ 'kind' => 'auto' ] ],
			] ),
			new StyleCase(
				'item settings never come from defaults or variants',
				$container,
				[ 'itemGrow' => [ 'desktop' => 2 ] ],
				[ 'itemGrow' => [ 'desktop' => 1 ], 'padding' => [ 'desktop' => [ 'top' => self::px( 4 ) ] ] ],
				[ 'hover' => [ 'itemGrow' => [ 'desktop' => 3 ] ] ],
				[],
				StyleCase::looks( [ 'itemOrder' => [ 'desktop' => 1 ], 'maxWidth' => [ 'desktop' => self::px( 600 ) ] ] )
			),
			new StyleCase( 'button border, radius, shadow, gradient, typography', $button, [
				'border'        => [
					'desktop' => [
						'top'    => [ 'width' => self::px( 2 ), 'style' => 'solid', 'color' => self::color( 'accent' ) ],
						'bottom' => [ 'width' => self::px( 2 ), 'style' => 'solid', 'color' => self::color( 'accent' ) ],
						'left'   => [ 'width' => self::px( -1 ), 'style' => 'ridge', 'color' => [ 'kind' => 'preset', 'preset' => 'spacing', 'slug' => '40' ] ],
					],
					'tablet'  => [ 'bottom' => [ 'style' => 'dashed' ] ],
					'mobile'  => [ 'top' => [ 'width' => [ 'kind' => 'auto' ] ] ],
				],
				'borderRadius'  => [ 'desktop' => [ 'top' => self::px( 8 ), 'bottom' => self::px( 8 ) ], 'tablet' => [ 'right' => self::preset( '40' ) ] ],
				'shadow'        => [ 'desktop' => [ 'kind' => 'preset', 'preset' => 'shadow', 'slug' => 'md' ], 'mobile' => [ 'kind' => 'preset', 'preset' => 'color', 'slug' => 'accent' ] ],
				'gradient'      => [ 'desktop' => [ 'kind' => 'preset', 'preset' => 'gradient', 'slug' => 'accent-fade' ] ],
				'lineHeight'    => [ 'desktop' => 1.25, 'tablet' => 11 ],
				'letterSpacing' => [ 'desktop' => [ 'kind' => 'custom', 'value' => -0.02, 'unit' => 'em' ] ],
			] ),
			new StyleCase( 'aspect ratio and position', $container, [
				'aspectRatio' => [ 'desktop' => [ 'width' => 16, 'height' => 9 ], 'tablet' => [ 'width' => 0, 'height' => 1 ], 'mobile' => [ 'width' => 1, 'height' => 1 ] ],
				'position'    => [ 'desktop' => 'sticky', 'mobile' => 'static' ],
				'inset'       => [ 'desktop' => [ 'top' => self::px( 0 ), 'left' => [ 'kind' => 'auto' ] ] ],
				'zIndex'      => [ 'desktop' => 10, 'tablet' => 1.5 ],
				'opacity'     => [ 'desktop' => 0.9, 'mobile' => 2 ],
			] ),
			new StyleCase( 'cover: fit, focal point, overlay', $container, [
				'mediaFit'       => [ 'desktop' => 'contain', 'mobile' => 'fill' ],
				'focalPoint'     => [ 'desktop' => [ 'x' => 0.25, 'y' => 0.333333 ], 'tablet' => [ 'x' => 1.5, 'y' => 0 ] ],
				'overlayColor'   => [ 'desktop' => self::color( 'x-dark' ) ],
				'overlayGradient' => [ 'desktop' => [ 'kind' => 'preset', 'preset' => 'gradient', 'slug' => 'accent-fade' ] ],
				'overlayOpacity' => [ 'desktop' => 0.6, 'mobile' => 0.3 ],
			] ),
			new StyleCase( 'garbage states', $container, [], [], [ 'hover' => 'nope', 'focus' => [ 'padding' => 'x' ] ] ),
			new StyleCase( 'garbage attribute', $container, [ 'padding' => 'nope', 'margin' => [ 'desktop' => 'nope' ] ] ),
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
