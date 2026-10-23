<?php

declare(strict_types=1);

namespace Fb\Values;

final readonly class CustomLength implements Length
{
	/** Sanity bound: anything larger is a typo, not a design. */
	public const float MAX_ABS = 100000.0;

	public function __construct(
		public float $value,
		public Unit $unit,
	) {
		if ( ! is_finite( $value ) || abs( $value ) > self::MAX_ABS ) {
			throw new \InvalidArgumentException( "Length {$value} is out of range." );
		}
	}

	public function isNegative(): bool
	{
		return $this->value < 0;
	}

	public function toCss(): string
	{
		$number = CssNumber::format( $this->value );

		return '0' === $number ? '0' : $number . $this->unit->value;
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{kind: 'custom', value: float, unit: string}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'custom', 'value' => $this->value, 'unit' => $this->unit->value ];
	}
}
