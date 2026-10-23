<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * Four optional lengths, one per side. What they mean (padding, margin,
 * gap…) is decided by the output that consumes them.
 *
 * Always stored per side: "linked" / "axis" / "sides" is only how the
 * editor UI groups them.
 */
final readonly class BoxValue implements ResponsiveValue
{
	public function __construct(
		public ?Length $top = null,
		public ?Length $right = null,
		public ?Length $bottom = null,
		public ?Length $left = null,
	) {
	}

	public static function all( Length $length ): self
	{
		return new self( $length, $length, $length, $length );
	}

	public function get( Side $side ): ?Length
	{
		return match ( $side ) {
			Side::Top    => $this->top,
			Side::Right  => $this->right,
			Side::Bottom => $this->bottom,
			Side::Left   => $this->left,
		};
	}

	public function isEmpty(): bool
	{
		return null === $this->top && null === $this->right && null === $this->bottom && null === $this->left;
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		if ( ! $larger instanceof self ) {
			return $this;
		}

		return new self(
			$this->top ?? $larger->top,
			$this->right ?? $larger->right,
			$this->bottom ?? $larger->bottom,
			$this->left ?? $larger->left,
		);
	}

	/**
	 * @throws InvalidJson
	 */
	public static function fromJson( JsonValue $json ): self
	{
		$read = static fn ( Side $side ): ?Length => $json->has( $side->value ) && ! $json->get( $side->value )->isNull()
			? Lengths::fromJson( $json->get( $side->value ) )
			: null;

		return new self( $read( Side::Top ), $read( Side::Right ), $read( Side::Bottom ), $read( Side::Left ) );
	}

	/**
	 * Only set sides are written.
	 *
	 * @return array<value-of<Side>, Length>
	 */
	public function jsonSerialize(): array
	{
		$json = [];
		foreach ( Side::cases() as $side ) {
			$length = $this->get( $side );
			if ( null !== $length ) {
				$json[ $side->value ] = $length;
			}
		}

		return $json;
	}
}
