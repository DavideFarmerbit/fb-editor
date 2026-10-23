<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\ResponsiveValue;

/**
 * How a control is edited, and what values it accepts.
 *
 * `parse()` is the sanitization point for stored values: anything the input
 * wouldn't have produced in the editor is rejected here.
 */
interface Input extends \JsonSerializable
{
	/**
	 * @throws InvalidJson
	 */
	public function parse( JsonValue $json ): ResponsiveValue;
}
