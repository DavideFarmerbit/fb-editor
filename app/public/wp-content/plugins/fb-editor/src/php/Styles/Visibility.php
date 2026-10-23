<?php

declare(strict_types=1);

namespace Fb\Styles;

use Fb\Breakpoints\BreakpointId;

/**
 * Breakpoints at which a control is hidden (its show-only-if condition
 * fails). A hidden control has no effect there, even if it inherits a
 * value from a larger breakpoint.
 */
final readonly class Visibility
{
	/**
	 * @param list<string> $hidden BreakpointId values.
	 */
	private function __construct(
		private array $hidden,
	) {
	}

	public static function always(): self
	{
		return new self( [] );
	}

	public static function hiddenAt( BreakpointId ...$breakpoints ): self
	{
		return new self( array_values( array_map( static fn ( BreakpointId $id ): string => $id->value, $breakpoints ) ) );
	}

	public function isVisible( BreakpointId $breakpoint ): bool
	{
		return ! in_array( $breakpoint->value, $this->hidden, true );
	}
}
