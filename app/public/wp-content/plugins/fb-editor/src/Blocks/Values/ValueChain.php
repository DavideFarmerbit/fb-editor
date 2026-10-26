<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Breakpoints\BreakpointId;

/**
 * Where a control's effective value comes from, strongest source first,
 * e.g. for a hovered block:
 *
 *   block hover → default hover → block base → default base
 *
 * Each source cascades across breakpoints on its own before the next one is
 * asked: a block value set at the base breakpoint beats a weaker source's
 * value at a smaller one, as the CSS layers do.
 */
final readonly class ValueChain
{
	/** @var list<BlockValues> */
	private array $sources;

	public function __construct(
		private BlockDefinition $definition,
		BlockValues ...$sources,
	) {
		$this->sources = array_values( $sources );
	}

	/**
	 * Every control's effective value at a breakpoint: what conditions are
	 * evaluated against.
	 */
	public function resolvedAt( BreakpointId $breakpoint ): ResolvedValues
	{
		$resolved = [];
		foreach ( $this->definition->controls as $control ) {
			foreach ( $this->sources as $source ) {
				$at = $source->get( $control->id )->resolve( $breakpoint, $source->breakpoints() );
				if ( null !== $at ) {
					$resolved[ $control->id->value ] = $at;
					break;
				}
			}
		}

		return new ResolvedValues( $resolved );
	}
}
