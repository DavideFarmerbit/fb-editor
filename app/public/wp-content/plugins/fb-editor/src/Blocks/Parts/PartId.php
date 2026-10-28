<?php

declare(strict_types=1);

namespace Fb\Blocks\Parts;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Stable key of a block's part, as stored in looks ("before", "image"…).
 */
final readonly class PartId implements \JsonSerializable, \Stringable
{
	#[ExportToTypeScript( regExp: true )]
	public const string PATTERN = '^[a-z][a-z0-9-]*$';

	public function __construct(
		public string $value,
	) {
		if ( 1 !== preg_match( '/' . self::PATTERN . '/', $value ) ) {
			throw new \InvalidArgumentException( "Invalid part id \"{$value}\": use lowercase letters, digits and -." );
		}
	}

	public static function tryFrom( string $value ): ?self
	{
		return 1 === preg_match( '/' . self::PATTERN . '/', $value ) ? new self( $value ) : null;
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
