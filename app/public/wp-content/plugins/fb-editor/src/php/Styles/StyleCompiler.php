<?php

declare(strict_types=1);

namespace Fb\Styles;

use Fb\Breakpoints\BreakpointList;
use Fb\Css\CssLayer;

/**
 * Compiles style bindings into one layered stylesheet, shaped like:
 *
 *   | (at)layer fb.instances{
 *   |   :where(.fb-a){padding-top:4rem;}
 *   |   (at)media (max-width: 1024px){ … }
 *   |   (at)media (max-width: 767px){ … }
 *   | }
 *
 * Desktop-first: base rules unqualified, smaller breakpoints in max-width
 * queries, largest first, so the cascade carries every value down.
 *
 * For each property the compiler tracks what the cascade already delivers
 * at each breakpoint and only writes what differs:
 * - a value equal to the inherited one is not repeated;
 * - a control hidden at a breakpoint gets `revert-layer` there, if it would
 *   otherwise inherit something;
 * - a control visible again below a hidden breakpoint re-states its value.
 */
final readonly class StyleCompiler
{
	public function __construct(
		private BreakpointList $breakpoints,
	) {
	}

	public function compile( CssLayer $layer, StyleBinding ...$bindings ): string
	{
		/** @var list<RuleSet> $byBreakpoint Same order as $this->breakpoints. */
		$byBreakpoint = [];
		foreach ( $this->breakpoints as $ignored ) {
			$byBreakpoint[] = new RuleSet();
		}

		foreach ( $bindings as $binding ) {
			$this->compileBinding( $binding, $byBreakpoint );
		}

		$css = '';
		foreach ( $this->breakpoints as $index => $breakpoint ) {
			$rules = $byBreakpoint[ $index ] ?? null;
			if ( null === $rules || $rules->isEmpty() ) {
				continue;
			}
			$query = $breakpoint->mediaQuery();
			$css  .= null === $query ? $rules->toCss() : "@media {$query}{{$rules->toCss()}}";
		}

		return '' === $css ? '' : $layer->wrap( $css );
	}

	/**
	 * @param list<RuleSet> $byBreakpoint
	 */
	private function compileBinding( StyleBinding $binding, array $byBreakpoint ): void
	{
		$cascaded = DeclarationSet::empty();

		foreach ( $this->breakpoints as $index => $breakpoint ) {
			$desired = $binding->visibility->isVisible( $breakpoint->id )
				? $binding->output->declarations( $binding->value->resolve( $breakpoint->id, $this->breakpoints ) )
				: DeclarationSet::empty();

			$emit = DeclarationSet::empty();

			foreach ( $binding->output->properties() as $property ) {
				$want = $desired->get( $property );
				$have = $cascaded->get( $property );

				if ( null === $want ) {
					// Nothing wanted here: only act if something cascades in.
					$want = null === $have || DeclarationSet::REVERT === $have ? null : DeclarationSet::REVERT;
				}

				if ( null !== $want && $want !== $have ) {
					$emit     = $emit->with( $property, $want );
					$cascaded = $cascaded->with( $property, $want );
				}
			}

			$byBreakpoint[ $index ]?->add( $binding->selector, $emit );
		}
	}
}
