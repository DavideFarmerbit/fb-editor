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
	 * @param Selector $root The selector the values apply to: the instance
	 *     class for instance values, the block class for type defaults.
	 * @return list<StyleBinding>
	 */
	public function bindings( BlockDefinition $definition, BlockValues $values, Selector $root ): array
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
					if ( ! $control->visibleWhen->evaluate( $values->resolvedAt( $breakpoint->id ) ) ) {
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
