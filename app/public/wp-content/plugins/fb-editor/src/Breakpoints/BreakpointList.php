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
	 * Smallest viewport width a breakpoint covers: one more than the next
	 * smaller breakpoint's maxWidth; null for the smallest breakpoint.
	 */
	public function minWidth( Breakpoint $breakpoint ): ?int
	{
		$index = array_search( $breakpoint, $this->items, true );
		$next  = false === $index ? null : ( $this->items[ $index + 1 ] ?? null );

		return null === $next?->maxWidth ? null : $next->maxWidth + 1;
	}

	/**
	 * What the editor receives. Everything derived (media query, covered
	 * range) is computed here, never in JS.
	 *
	 * @return list<array{id: BreakpointId, label: string, maxWidth: ?int, minWidth: ?int, previewWidth: ?int, mediaQuery: ?string}>
	 */
	public function jsonSerialize(): array
	{
		return array_map(
			fn ( Breakpoint $breakpoint ): array => [
				'id'           => $breakpoint->id,
				'label'        => $breakpoint->label,
				'maxWidth'     => $breakpoint->maxWidth,
				'minWidth'     => $this->minWidth( $breakpoint ),
				'previewWidth' => $breakpoint->previewWidth,
				'mediaQuery'   => $breakpoint->mediaQuery(),
			],
			$this->items
		);
	}
}
