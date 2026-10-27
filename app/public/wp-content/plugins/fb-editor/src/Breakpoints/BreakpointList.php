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
	/**
	 * Preview width of the smallest breakpoint when it sets none: it has
	 * no minimum width to fall back to. A common phone width.
	 */
	public const int SMALLEST_PREVIEW_WIDTH = 375;

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

		foreach ( $this->items as $breakpoint ) {
			$width = $this->previewWidth( $breakpoint );
			$min   = $this->minWidth( $breakpoint );
			if ( ( null !== $min && $width < $min ) || ( null !== $breakpoint->maxWidth && $width > $breakpoint->maxWidth ) ) {
				throw new \InvalidArgumentException( "Breakpoint \"{$breakpoint->id}\": previewWidth {$width} is outside its own range." );
			}
		}
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
	 * Media query matching exactly the widths a breakpoint covers (not
	 * "from there down", like mediaQuery()); null when that's every width
	 * (a single breakpoint).
	 */
	public function bandQuery( Breakpoint $breakpoint ): ?string
	{
		$min   = $this->minWidth( $breakpoint );
		$parts = [];
		if ( null !== $min ) {
			$parts[] = "(min-width: {$min}px)";
		}
		if ( null !== $breakpoint->maxWidth ) {
			$parts[] = "(max-width: {$breakpoint->maxWidth}px)";
		}

		return [] === $parts ? null : implode( ' and ', $parts );
	}

	/**
	 * The editor canvas width while previewing a breakpoint: always fixed,
	 * so the window size never decides which media queries apply. Its own
	 * previewWidth, else the smallest width it covers, else (the smallest
	 * breakpoint) SMALLEST_PREVIEW_WIDTH, capped to its maxWidth.
	 */
	public function previewWidth( Breakpoint $breakpoint ): int
	{
		return $breakpoint->previewWidth
			?? $this->minWidth( $breakpoint )
			?? min( self::SMALLEST_PREVIEW_WIDTH, $breakpoint->maxWidth ?? self::SMALLEST_PREVIEW_WIDTH );
	}

	/**
	 * What the editor receives. Everything derived (media query, covered
	 * range) is computed here, never in JS.
	 *
	 * @return list<array{id: BreakpointId, label: string, maxWidth: ?int, minWidth: ?int, previewWidth: int, mediaQuery: ?string}>
	 */
	public function jsonSerialize(): array
	{
		return array_map(
			fn ( Breakpoint $breakpoint ): array => [
				'id'           => $breakpoint->id,
				'label'        => $breakpoint->label,
				'maxWidth'     => $breakpoint->maxWidth,
				'minWidth'     => $this->minWidth( $breakpoint ),
				'previewWidth' => $this->previewWidth( $breakpoint ),
				'mediaQuery'   => $breakpoint->mediaQuery(),
			],
			$this->items
		);
	}
}
