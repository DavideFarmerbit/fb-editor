<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

/**
 * A named inner element of a block, rendered as a direct child of the
 * block root with class `<block class>__<element>` (e.g. fb-section__inner).
 */
final readonly class ElementId implements \JsonSerializable, \Stringable
{
	public function __construct(
		public string $value,
	) {
		if ( 1 !== preg_match( '/^[a-z][a-z0-9-]*$/', $value ) ) {
			throw new \InvalidArgumentException( "Invalid element id \"{$value}\": use lowercase letters, digits and -." );
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
