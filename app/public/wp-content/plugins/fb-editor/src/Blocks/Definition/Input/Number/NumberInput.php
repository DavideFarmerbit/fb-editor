<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Number;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\NumberValue;

/**
 * A plain number without unit, within optional bounds; integers only when
 * `integer` (order, spans).
 */
final readonly class NumberInput implements Input
{
	public function __construct(
		public ?float $min = null,
		public ?float $max = null,
		/** Step of the editor's arrows; not enforced on stored values. */
		public float $step = 1.0,
		public bool $integer = false,
	) {
		if ( null !== $min && null !== $max && $min > $max ) {
			throw new \InvalidArgumentException( 'NumberInput: min is greater than max.' );
		}
		if ( $step <= 0 ) {
			throw new \InvalidArgumentException( 'NumberInput: step must be positive.' );
		}
	}

	public function allows( NumberValue $number ): bool
	{
		$value = (float) $number->value;

		return ( null === $this->min || $value >= $this->min )
			&& ( null === $this->max || $value <= $this->max )
			&& ( ! $this->integer || is_int( $number->value ) );
	}

	public function parse( JsonValue $json ): NumberValue
	{
		$raw = $json->float();
		// Integers stay integers (also when stored as 2.0): one canonical form.
		$number = $this->integer && floor( $raw ) === $raw ? new NumberValue( (int) $raw ) : new NumberValue( $raw );

		if ( ! $this->allows( $number ) ) {
			throw new InvalidJson( "{$json->path}: number not allowed by this input" );
		}

		return $number;
	}

	/**
	 * @return array{kind: 'number', min: ?float, max: ?float, step: float, integer: bool}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'number', 'min' => $this->min, 'max' => $this->max, 'step' => $this->step, 'integer' => $this->integer ];
	}
}
