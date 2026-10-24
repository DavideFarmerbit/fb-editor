<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Color;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Presets\PresetKind;
use Fb\Values\Color;
use Fb\Values\Colors;
use Fb\Values\CustomColor;
use Fb\Values\PresetReference;

/**
 * Pick a theme palette color, or (if allowed) a custom hex color.
 */
final readonly class ColorInput implements Input
{
	public function __construct(
		public bool $allowCustom = true,
	) {
	}

	public function allows( Color $color ): bool
	{
		return match ( true ) {
			$color instanceof PresetReference => PresetKind::Color === $color->preset,
			$color instanceof CustomColor     => $this->allowCustom,
			default                           => false,
		};
	}

	public function parse( JsonValue $json ): Color
	{
		$color = Colors::fromJson( $json );

		if ( ! $this->allows( $color ) ) {
			throw new InvalidJson( "{$json->path}: color not allowed by this input" );
		}

		return $color;
	}

	/**
	 * @return array{kind: 'color', allowCustom: bool}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'color', 'allowCustom' => $this->allowCustom ];
	}
}
