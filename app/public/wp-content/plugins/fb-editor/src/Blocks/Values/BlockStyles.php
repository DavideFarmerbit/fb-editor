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
	 * Every look of one source: its base on `$root`, each state on `$root`
	 * with the state's pseudo-class.
	 *
	 * @param Selector       $root   The instance class for a block, the
	 *     block class for type defaults.
	 * @param StatefulValues ...$weaker Sources below this one (a block's type
	 *     defaults), for conditions: they read the strongest value.
	 */
	public function forSource( BlockDefinition $definition, StatefulValues $values, Selector $root, StatefulValues ...$weaker ): BlockBindings
	{
		$states = [];
		foreach ( $definition->states as $state ) {
			$bindings = $this->bindings( $definition, $values->state( $state ), $root->withPseudoClass( $state->pseudoClass() ), $values->chain( $state, ...$weaker ) );
			if ( [] !== $bindings ) {
				$states[ $state->value ] = $bindings;
			}
		}

		return new BlockBindings( $this->bindings( $definition, $values->base, $root, $values->chain( null, ...$weaker ) ), $states );
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
