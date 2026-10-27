<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A plain number, no unit (line-height, flex-grow, order…). Which numbers
 * are allowed is decided by the input that produces it.
 */
final readonly class NumberValue implements CssValue, ResponsiveValue
{
	public int|float $value;

	public function __construct( int|float $value )
	{
		if ( ! is_finite( (float) $value ) ) {
			throw new \InvalidArgumentException( 'Number must be finite.' );
		}
		// -0 and 0 are the same number: one canonical form, one instance hash.
		$this->value = 0.0 === (float) $value ? 0 : $value;
	}

	public function toCss(): string
	{
		return CssNumber::format( (float) $this->value );
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	public function jsonSerialize(): int|float
	{
		return $this->value;
	}
}
