<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\Breakpoints\BreakpointId;
use Fb\Breakpoints\BreakpointList;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * A value set per breakpoint, stored under breakpoint ids.
 *
 * Serialized as `{"desktop": T, "mobile": T}`. Unknown ids (a breakpoint
 * removed from fb.json) are dropped on parse, never break rendering.
 *
 * @template-covariant T of ResponsiveValue
 */
final readonly class Responsive implements \JsonSerializable
{
	/**
	 * @param array<string, T> $values Keyed by BreakpointId value. Private storage.
	 */
	private function __construct(
		private array $values,
	) {
	}

	/**
	 * @return self<never>
	 */
	public static function empty(): self
	{
		return new self( [] );
	}

	/**
	 * @template V of ResponsiveValue
	 * @param V $value
	 * @return self<V>
	 */
	public static function base( BreakpointList $breakpoints, ResponsiveValue $value ): self
	{
		return new self( [ $breakpoints->base->id->value => $value ] );
	}

	/**
	 * @template V of ResponsiveValue
	 * @param V $value
	 * @return self<T|V>
	 */
	public function with( BreakpointId $breakpoint, ResponsiveValue $value ): self
	{
		return new self( [ ...$this->values, $breakpoint->value => $value ] );
	}

	/**
	 * The value set at exactly this breakpoint.
	 *
	 * @return T|null
	 */
	public function at( BreakpointId $breakpoint ): ?ResponsiveValue
	{
		return $this->values[ $breakpoint->value ] ?? null;
	}

	/**
	 * The effective value at a breakpoint: walks from the base down to it,
	 * letting each smaller breakpoint's value inherit what it leaves unset.
	 *
	 * @return T|null
	 */
	public function resolve( BreakpointId $breakpoint, BreakpointList $breakpoints ): ?ResponsiveValue
	{
		$resolved = null;

		foreach ( $breakpoints as $candidate ) {
			$own = $this->at( $candidate->id );
			if ( null !== $own ) {
				$resolved = null === $resolved ? $own : $own->inheritFrom( $resolved );
			}
			if ( $candidate->id->equals( $breakpoint ) ) {
				return $resolved;
			}
		}

		return null;
	}

	public function isEmpty(): bool
	{
		return [] === $this->values;
	}

	/**
	 * Per breakpoint, `$over`'s value where it has one, else this one.
	 *
	 * @template V of ResponsiveValue
	 * @param self<V> $over
	 * @return self<T|V>
	 */
	public function overlay( self $over, BreakpointList $breakpoints ): self
	{
		$values = [];
		foreach ( $breakpoints as $breakpoint ) {
			$value = $over->at( $breakpoint->id ) ?? $this->at( $breakpoint->id );
			if ( null !== $value ) {
				$values[ $breakpoint->id->value ] = $value;
			}
		}

		return new self( $values );
	}

	/**
	 * @template V of ResponsiveValue
	 * @param \Closure(JsonValue): V $parseValue
	 * @return self<V>
	 * @throws InvalidJson When the container itself isn't an object.
	 */
	public static function fromJson( JsonValue $json, \Closure $parseValue, BreakpointList $breakpoints ): self
	{
		$values = [];

		foreach ( $json->properties() as $id => $child ) {
			if ( null === self::knownId( $id, $breakpoints ) || $child->isNull() ) {
				continue;
			}
			try {
				$values[ $id ] = $parseValue( $child );
			} catch ( InvalidJson ) {
				// One bad value never takes down the others.
				continue;
			}
		}

		return new self( $values );
	}

	private static function knownId( string $id, BreakpointList $breakpoints ): ?BreakpointId
	{
		try {
			$breakpointId = new BreakpointId( $id );
		} catch ( \InvalidArgumentException ) {
			return null;
		}

		return null === $breakpoints->get( $breakpointId ) ? null : $breakpointId;
	}

	/**
	 * Always a JSON object, even when empty.
	 *
	 * @return array<string, T>|\stdClass
	 */
	public function jsonSerialize(): array|\stdClass
	{
		return [] === $this->values ? new \stdClass() : $this->values;
	}
}
