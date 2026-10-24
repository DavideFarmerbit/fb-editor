<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Spacing;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Presets\PresetKind;
use Fb\Values\AutoLength;
use Fb\Values\BoxValue;
use Fb\Values\CustomLength;
use Fb\Values\Length;
use Fb\Values\Lengths;
use Fb\Values\PresetLength;
use Fb\Values\Side;

/**
 * Spacing slider over the theme's spacing scale, with a custom value + unit
 * toggle and linked / axis / sides modes. Produces a BoxValue.
 */
final readonly class SpacingInput implements Input
{
	/** @var non-empty-list<SpacingMode> */
	public array $modes;

	public function __construct(
		public bool $allowAuto = false,
		public bool $allowNegative = false,
		SpacingMode ...$modes,
	) {
		$this->modes = [] === $modes ? SpacingMode::cases() : array_values( $modes );
	}

	public static function padding(): self
	{
		return new self();
	}

	public static function margin(): self
	{
		return new self( true, true );
	}

	public static function gap(): self
	{
		return new self( false, false, SpacingMode::Linked, SpacingMode::Axis );
	}

	public function parse( JsonValue $json ): BoxValue
	{
		if ( ! $json->isObject() ) {
			throw new InvalidJson( "{$json->path}: expected an object" );
		}

		// Sides are validated one by one: an invalid side is dropped, the
		// others survive.
		$side = function ( Side $side ) use ( $json ): ?Length {
			if ( ! $json->has( $side->value ) || $json->get( $side->value )->isNull() ) {
				return null;
			}
			try {
				$length = Lengths::fromJson( $json->get( $side->value ) );
			} catch ( InvalidJson ) {
				return null;
			}

			return $this->allows( $length ) ? $length : null;
		};

		$box = new BoxValue( $side( Side::Top ), $side( Side::Right ), $side( Side::Bottom ), $side( Side::Left ) );

		if ( $box->isEmpty() ) {
			throw new InvalidJson( "{$json->path}: no valid side" );
		}

		return $box;
	}

	private function allows( Length $length ): bool
	{
		return match ( true ) {
			$length instanceof AutoLength   => $this->allowAuto,
			$length instanceof CustomLength => $this->allowNegative || ! $length->isNegative(),
			$length instanceof PresetLength => PresetKind::Spacing === $length->preset,
			default                         => false,
		};
	}

	/**
	 * @return array{kind: 'spacing', allowAuto: bool, allowNegative: bool, modes: non-empty-list<SpacingMode>}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'          => 'spacing',
			'allowAuto'     => $this->allowAuto,
			'allowNegative' => $this->allowNegative,
			'modes'         => $this->modes,
		];
	}
}
