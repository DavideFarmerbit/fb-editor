<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Toggle;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\JsonValue;
use Fb\Values\BooleanValue;

/**
 * On/off switch. Stores a boolean; the control's output (e.g. ToggleOutput)
 * or the block's render decides what it means.
 */
final readonly class ToggleInput implements Input
{
	public function parse( JsonValue $json ): BooleanValue
	{
		return new BooleanValue( $json->bool() );
	}

	/**
	 * @return array{kind: 'toggle'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'toggle' ];
	}
}
