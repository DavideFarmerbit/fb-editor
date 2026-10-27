<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A border, per side. Always stored per side: "linked" / "axis" / "sides"
 * is only how the editor groups them (as for BoxValue).
 */
final readonly class BorderBox implements ResponsiveValue
{
	public function __construct(
		public ?BorderSide $top = null,
		public ?BorderSide $right = null,
		public ?BorderSide $bottom = null,
		public ?BorderSide $left = null,
	) {
	}

	public function get( Side $side ): ?BorderSide
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

	/** Per side and part: what this leaves unset comes from `$larger`. */
	public function inheritFrom( ResponsiveValue $larger ): static
	{
		if ( ! $larger instanceof self ) {
			return $this;
		}

		$side = static fn ( ?BorderSide $own, ?BorderSide $from ): ?BorderSide => null === $own ? $from : $own->inheritFrom( $from );

		return new self(
			$side( $this->top, $larger->top ),
			$side( $this->right, $larger->right ),
			$side( $this->bottom, $larger->bottom ),
			$side( $this->left, $larger->left ),
		);
	}

	/**
	 * Only set sides are written.
	 *
	 * @return array<value-of<Side>, BorderSide>
	 */
	public function jsonSerialize(): array
	{
		$json = [];
		foreach ( Side::cases() as $side ) {
			$value = $this->get( $side );
			if ( null !== $value ) {
				$json[ $side->value ] = $value;
			}
		}

		return $json;
	}
}
