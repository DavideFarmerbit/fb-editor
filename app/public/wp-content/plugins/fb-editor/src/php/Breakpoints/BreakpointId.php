<?php

declare(strict_types=1);

namespace Fb\Breakpoints;

/**
 * Stable key that responsive values are stored under. Never a pixel width.
 */
final readonly class BreakpointId implements \JsonSerializable, \Stringable
{
	public function __construct(
		public string $value,
	) {
		if ( 1 !== preg_match( '/^[a-z][a-z0-9_-]*$/', $value ) ) {
			throw new \InvalidArgumentException( "Invalid breakpoint id \"{$value}\": use lowercase letters, digits, - and _." );
		}
	}

	public function equals( self $other ): bool
	{
		return $this->value === $other->value;
	}

	public function __toString(): string
	{
		return $this->value;
	}

	public function jsonSerialize(): string
	{
		return $this->value;
	}
}
