<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * Where to keep a cropped image's subject: x and y from 0 (left/top) to 1
 * (right/bottom) → `object-position: 30% 70%`.
 */
final readonly class FocalPoint implements CssValue, ResponsiveValue
{
	public function __construct(
		public float $x,
		public float $y,
	) {
		foreach ( [ $x, $y ] as $axis ) {
			if ( ! is_finite( $axis ) || $axis < 0 || $axis > 1 ) {
				throw new \InvalidArgumentException( "Focal point {$axis} is outside 0–1." );
			}
		}
	}

	public function toCss(): string
	{
		return CssNumber::format( $this->x * 100 ) . '% ' . CssNumber::format( $this->y * 100 ) . '%';
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{x: float, y: float}
	 */
	public function jsonSerialize(): array
	{
		return [ 'x' => $this->x, 'y' => $this->y ];
	}
}
