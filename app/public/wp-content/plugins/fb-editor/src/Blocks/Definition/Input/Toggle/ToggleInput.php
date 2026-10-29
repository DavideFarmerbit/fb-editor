<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Toggle;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\JsonValue;
use Fb\Values\BooleanValue;

/**
 * On/off switch. Stores a boolean; the control's output (e.g. ToggleOutput)
 * or the block's render decides what it means.
 *
 * `fallback` is what applies while nothing is stored (the editor shows it).
 */
final readonly class ToggleInput implements Input
{
	public function __construct(
		public bool $fallback = false,
	) {
	}

	public function parse( JsonValue $json ): BooleanValue
	{
		return new BooleanValue( $json->bool() );
	}

	/**
	 * @return array{kind: 'toggle', fallback: bool}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'toggle', 'fallback' => $this->fallback ];
	}
}
