<?php

declare(strict_types=1);

namespace Fb\Breakpoints;

/**
 * Desktop-first ordered breakpoints: base first, then by maxWidth descending.
 *
 * Invariants (enforced on construction):
 * - exactly one base breakpoint, and it comes first;
 * - unique ids and unique maxWidths.
 *
 * @implements \IteratorAggregate<int, Breakpoint>
 */
final readonly class BreakpointList implements \IteratorAggregate, \Countable, \JsonSerializable
{
	/** @var list<Breakpoint> */
	private array $items;

	public function __construct( public Breakpoint $base, Breakpoint ...$others )
	{
		if ( ! $base->isBase() ) {
			throw new \InvalidArgumentException( "Base breakpoint \"{$base->id}\" must not have a maxWidth." );
		}

		$ids    = [ $base->id->value => true ];
		$widths = [];

		foreach ( $others as $breakpoint ) {
			if ( null === $breakpoint->maxWidth ) {
				throw new \InvalidArgumentException( "Breakpoint \"{$breakpoint->id}\" needs a maxWidth: only the base has none." );
			}
			if ( isset( $ids[ $breakpoint->id->value ] ) ) {
				throw new \InvalidArgumentException( "Duplicate breakpoint id \"{$breakpoint->id}\"." );
			}
			if ( isset( $widths[ $breakpoint->maxWidth ] ) ) {
				throw new \InvalidArgumentException( "Duplicate breakpoint maxWidth {$breakpoint->maxWidth}." );
			}
			$ids[ $breakpoint->id->value ] = true;
			$widths[ $breakpoint->maxWidth ] = true;
		}

		usort( $others, static fn ( Breakpoint $a, Breakpoint $b ): int => $b->maxWidth <=> $a->maxWidth );

		$this->items = [ $base, ...$others ];
	}

	public function get( BreakpointId $id ): ?Breakpoint
	{
		foreach ( $this->items as $breakpoint ) {
			if ( $breakpoint->id->equals( $id ) ) {
				return $breakpoint;
			}
		}

		return null;
	}

	/**
	 * @return \ArrayIterator<int, Breakpoint>
	 */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator( $this->items );
	}

	public function count(): int
	{
		return count( $this->items );
	}

	/**
	 * @return list<Breakpoint>
	 */
	public function jsonSerialize(): array
	{
		return $this->items;
	}
}
