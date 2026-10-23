<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Condition;

use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Values\ResolvedValues;

/**
 * A show-only-if rule, evaluated per breakpoint against resolved values.
 * Pure data, serialized with a `kind` tag, so the editor evaluates the same
 * tree in JS.
 */
interface Condition extends \JsonSerializable
{
	public function evaluate( ResolvedValues $values ): bool;

	/**
	 * Controls this condition reads (validated against the block's controls).
	 *
	 * @return list<ControlId>
	 */
	public function references(): array;
}
