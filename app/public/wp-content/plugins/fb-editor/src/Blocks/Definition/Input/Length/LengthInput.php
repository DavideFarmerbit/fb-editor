<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Length;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Presets\PresetKind;
use Fb\Values\AutoLength;
use Fb\Values\CustomLength;
use Fb\Values\Length;
use Fb\Values\Lengths;
use Fb\Values\PresetReference;

/**
 * One length (width, max-width, min-height…): a custom number + unit,
 * optionally a step of a theme scale, optionally `auto` / negatives.
 */
final readonly class LengthInput implements Input
{
	public function __construct(
		public bool $allowAuto = false,
		public bool $allowNegative = false,
		/** Theme scale offered as a slider; null for custom values only. */
		public ?PresetKind $scale = null,
	) {
	}

	public function allows( Length $length ): bool
	{
		return match ( true ) {
			$length instanceof AutoLength      => $this->allowAuto,
			$length instanceof CustomLength    => $this->allowNegative || ! $length->isNegative(),
			$length instanceof PresetReference => null !== $this->scale && $this->scale === $length->preset,
			default                            => false,
		};
	}

	public function parse( JsonValue $json ): Length
	{
		$length = Lengths::fromJson( $json );

		if ( ! $this->allows( $length ) ) {
			throw new InvalidJson( "{$json->path}: length not allowed by this input" );
		}

		return $length;
	}

	/**
	 * @return array{kind: 'length', allowAuto: bool, allowNegative: bool, scale: ?string}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'          => 'length',
			'allowAuto'     => $this->allowAuto,
			'allowNegative' => $this->allowNegative,
			'scale'         => $this->scale?->value,
		];
	}
}
