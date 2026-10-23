<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A value that renders to a safe CSS value string by construction.
 */
interface CssValue
{
	public function toCss(): string;
}
