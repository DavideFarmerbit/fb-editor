<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\TypeScript\ExportToTypeScript;

/**
 * A width:height ratio, e.g. 16:9 → `aspect-ratio: 16/9`.
 */
final readonly class AspectRatio implements CssValue, ResponsiveValue
{
	/** Sanity bound for either side of the ratio. */
	#[ExportToTypeScript]
	public const float MAX = 1000.0;

	public function __construct(
		public float $width,
		public float $height,
	) {
		foreach ( [ $width, $height ] as $side ) {
			if ( ! is_finite( $side ) || $side <= 0 || $side > self::MAX ) {
				throw new \InvalidArgumentException( "Aspect ratio side {$side} is out of range." );
			}
		}
	}

	public function toCss(): string
	{
		return CssNumber::format( $this->width ) . '/' . CssNumber::format( $this->height );
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{width: float, height: float}
	 */
	public function jsonSerialize(): array
	{
		return [ 'width' => $this->width, 'height' => $this->height ];
	}
}
