<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * `count` equal columns that may shrink below their content:
 * repeat(3,minmax(0,1fr)).
 */
final readonly class GridColumnCount implements GridColumns
{
	public function __construct(
		public int $count,
	) {
		if ( $count < 1 ) {
			throw new \InvalidArgumentException( 'A grid needs at least one column.' );
		}
	}

	public function toCss(): string
	{
		return "repeat({$this->count},minmax(0,1fr))";
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{kind: 'count', count: int}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'count', 'count' => $this->count ];
	}
}
