<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Presets\PresetKind;
use Fb\Values\AutoLength;
use Fb\Values\BoxValue;
use Fb\Values\CustomLength;
use Fb\Values\Length;
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
		$box = BoxValue::fromJson( $json );

		foreach ( Side::cases() as $side ) {
			$length = $box->get( $side );
			if ( null !== $length ) {
				$this->validate( $length, "{$json->path}.{$side->value}" );
			}
		}

		return $box;
	}

	/**
	 * @throws InvalidJson
	 */
	private function validate( Length $length, string $path ): void
	{
		$valid = match ( true ) {
			$length instanceof AutoLength   => $this->allowAuto,
			$length instanceof CustomLength => $this->allowNegative || ! $length->isNegative(),
			$length instanceof PresetLength => PresetKind::Spacing === $length->preset,
			default                         => false,
		};

		if ( ! $valid ) {
			throw new InvalidJson( "{$path}: value not allowed by this spacing input" );
		}
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
