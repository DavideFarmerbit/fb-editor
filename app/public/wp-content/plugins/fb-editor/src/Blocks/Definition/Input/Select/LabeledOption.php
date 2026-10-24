<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Select;

/**
 * Implemented by string-backed enums used as SelectInput options.
 * The case value is the stored value and the CSS keyword.
 */
interface LabeledOption extends \BackedEnum
{
	public function label(): string;
}
