<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

/**
 * Key a control's value is stored under in the block's `fb` attribute.
 */
final readonly class ControlId implements \JsonSerializable, \Stringable
{
	public function __construct(
		public string $value,
	) {
		if ( 1 !== preg_match( '/^[a-z][a-zA-Z0-9]*$/', $value ) ) {
			throw new \InvalidArgumentException( "Invalid control id \"{$value}\": use camelCase letters and digits." );
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
