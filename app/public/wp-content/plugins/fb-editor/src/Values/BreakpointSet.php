<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\Breakpoints\BreakpointId;

/**
 * Some breakpoints, picked one by one (e.g. where a block is hidden). Each
 * stands for its own range of widths only: nothing cascades. May be empty:
 * "none", which still overrides a type default's or variant's pick.
 */
final readonly class BreakpointSet implements ResponsiveValue
{
	/** @var list<BreakpointId> */
	public array $breakpoints;

	public function __construct( BreakpointId ...$breakpoints )
	{
		$unique = [];
		foreach ( $breakpoints as $breakpoint ) {
			$unique[ $breakpoint->value ] = $breakpoint;
		}
		$this->breakpoints = array_values( $unique );
	}

	public function has( BreakpointId $breakpoint ): bool
	{
		foreach ( $this->breakpoints as $candidate ) {
			if ( $candidate->equals( $breakpoint ) ) {
				return true;
			}
		}

		return false;
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return list<BreakpointId>
	 */
	public function jsonSerialize(): array
	{
		return $this->breakpoints;
	}
}
