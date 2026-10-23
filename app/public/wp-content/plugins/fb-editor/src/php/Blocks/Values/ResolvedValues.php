<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\Definition\ControlId;
use Fb\Values\ResponsiveValue;

/**
 * Every control's effective value at one breakpoint (own or inherited).
 * What conditions are evaluated against.
 */
final readonly class ResolvedValues
{
	/**
	 * @param array<string, ResponsiveValue> $values Keyed by ControlId value. Private storage.
	 */
	public function __construct(
		private array $values,
	) {
	}

	public function get( ControlId $id ): ?ResponsiveValue
	{
		return $this->values[ $id->value ] ?? null;
	}
}
