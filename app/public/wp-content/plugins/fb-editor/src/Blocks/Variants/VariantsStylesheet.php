<?php

declare(strict_types=1);

namespace Fb\Blocks\Variants;

use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Values\BlockBindings;
use Fb\Blocks\Values\BlockStyles;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Css\CssLayer;
use Fb\Styles\Selector;
use Fb\Styles\StyleCompiler;

/**
 * The `fb.variants` layers: each used variant on its class, e.g.
 * `:where(.fb-button--primary){…}`, above the type defaults and below the
 * blocks' own values. Conditions read the variant, then the type defaults.
 */
final readonly class VariantsStylesheet
{
	public function __construct(
		private DefaultsRepository $defaults,
		private BreakpointConfig $breakpoints,
	) {
	}

	/**
	 * @param list<UsedVariant> $used
	 */
	public function css( array $used ): string
	{
		$breakpoints = $this->breakpoints->breakpoints();
		$styles      = new BlockStyles( $breakpoints );
		$bindings    = new BlockBindings();

		foreach ( $used as $item ) {
			$bindings = $bindings->merge(
				$styles->forSource( $item->definition, $item->variant->values, Selector::forClass( $item->className() ), $this->defaults->for( $item->definition ) )
			);
		}

		return $bindings->css( new StyleCompiler( $breakpoints ), CssLayer::Variants );
	}
}
