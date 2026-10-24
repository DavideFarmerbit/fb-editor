<?php

declare(strict_types=1);

namespace Fb\Blocks\Defaults;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Values\BlockStyles;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Css\CssLayer;
use Fb\Styles\Selector;
use Fb\Styles\StyleCompiler;

/**
 * The `fb.defaults` layer: each block type's defaults on its block class,
 * e.g. `:where(.fb-section){…}`. Instances override them from the higher
 * `fb.instances` layer, at every breakpoint.
 */
final readonly class DefaultsStylesheet
{
	public function __construct(
		private DefaultsRepository $defaults,
		private BreakpointConfig $breakpoints,
	) {
	}

	/**
	 * @param list<BlockDefinition> $definitions Block types on the page.
	 */
	public function css( array $definitions ): string
	{
		$breakpoints = $this->breakpoints->breakpoints();
		$styles      = new BlockStyles( $breakpoints );
		$bindings    = [];

		foreach ( $definitions as $definition ) {
			array_push(
				$bindings,
				...$styles->bindings( $definition, $this->defaults->for( $definition ), Selector::forClass( $definition->name->cssClass() ) )
			);
		}

		return ( new StyleCompiler( $breakpoints ) )->compile( CssLayer::Defaults, ...$bindings );
	}
}
