<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Breakpoints\BreakpointList;
use Fb\Styles\Selector;
use Fb\Styles\StyleBinding;
use Fb\Styles\Visibility;

/**
 * Turns a block definition + values into style bindings for the compiler:
 * one binding per control with an output, targeting the root or its element,
 * hidden at every breakpoint where its condition fails.
 */
final readonly class BlockStyles
{
	public function __construct(
		private BreakpointList $breakpoints,
	) {
	}

	/**
	 * Every look of one source, each on `$root` with its states'
	 * pseudo-classes, its conditions read from the look's own chain.
	 *
	 * @param Selector       $root   The instance class for a block, the
	 *     block class for type defaults.
	 * @param StatefulValues ...$weaker Sources below this one (a block's type
	 *     defaults), for conditions: they read the strongest value.
	 */
	public function forSource( BlockDefinition $definition, StatefulValues $values, Selector $root, StatefulValues ...$weaker ): BlockBindings
	{
		$bindings = new BlockBindings();
		foreach ( $values->looks() as $look ) {
			$selector = $look->key->hasStates() ? $root->withPseudoClass( $look->key->pseudoClass() ) : $root;
			$bindings = $bindings->with( $look->key, ...$this->bindings( $definition, $look->values, $selector, $values->chain( $look->key, ...$weaker ) ) );
		}

		return $bindings;
	}

	/**
	 * @param ValueChain $context What conditions read (starting with `$values`).
	 * @return list<StyleBinding>
	 */
	public function bindings( BlockDefinition $definition, BlockValues $values, Selector $root, ValueChain $context ): array
	{
		$bindings = [];

		foreach ( $definition->controls as $control ) {
			$value = $values->get( $control->id );
			if ( null === $control->output || $value->isEmpty() ) {
				continue;
			}

			$hidden = [];
			if ( null !== $control->visibleWhen ) {
				foreach ( $this->breakpoints as $breakpoint ) {
					if ( ! $control->visibleWhen->evaluate( $context->resolvedAt( $breakpoint->id ) ) ) {
						$hidden[] = $breakpoint->id;
					}
				}
			}

			$bindings[] = new StyleBinding(
				null === $control->element ? $root : $root->child( $definition->name->elementClass( $control->element ) ),
				$control->output,
				$value,
				Visibility::hiddenAt( ...$hidden )
			);
		}

		return $bindings;
	}
}
