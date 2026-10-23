<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Values\ResponsiveValue;

/**
 * Declarative mapping from a control's value to CSS declarations.
 * Pure data (no callbacks) so the editor can mirror it exactly in JS.
 */
interface StyleOutput extends \JsonSerializable
{
	/**
	 * Every property this output can write (used to revert it when hidden).
	 *
	 * @return list<CssProperty>
	 */
	public function properties(): array;

	/**
	 * Declarations for a resolved value. Values of the wrong type, and
	 * unset parts of a value, produce no declarations.
	 */
	public function declarations( ?ResponsiveValue $value ): DeclarationSet;
}
