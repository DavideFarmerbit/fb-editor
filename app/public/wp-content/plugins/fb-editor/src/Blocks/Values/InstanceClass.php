<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

/**
 * Per-instance class, e.g. "fb-k3x9q2a": a short hash of the block's
 * canonical values. Identical settings share a class (and one CSS rule);
 * duplicated blocks can never clash.
 */
final readonly class InstanceClass implements \Stringable
{
	public const int LENGTH = 7;

	private function __construct(
		public string $value,
	) {
	}

	public static function of( BlockAttribute $values ): self
	{
		$json = (string) wp_json_encode( $values, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION );
		// 48 bits of sha1, base 36: well within float precision.
		$hash = base_convert( substr( sha1( $json ), 0, 12 ), 16, 36 );

		return new self( 'fb-' . str_pad( substr( $hash, 0, self::LENGTH ), self::LENGTH, '0', STR_PAD_LEFT ) );
	}

	public function __toString(): string
	{
		return $this->value;
	}
}
