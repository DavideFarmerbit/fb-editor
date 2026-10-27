<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * As many equal columns as fit, each at least `min` wide (never wider than
 * the grid itself): repeat(auto-fit,minmax(min(240px,100%),1fr)).
 */
final readonly class GridColumnFit implements GridColumns
{
	public function __construct(
		public CustomLength $min,
	) {
		if ( $min->value <= 0 ) {
			throw new \InvalidArgumentException( 'A column minimum must be positive.' );
		}
	}

	public function toCss(): string
	{
		return "repeat(auto-fit,minmax(min({$this->min->toCss()},100%),1fr))";
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{kind: 'fit', min: CustomLength}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'fit', 'min' => $this->min ];
	}
}
