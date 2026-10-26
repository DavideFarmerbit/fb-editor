<?php

declare(strict_types=1);

namespace Fb\Blocks\Variants;

use Fb\TypeScript\ExportToTypeScript;

/**
 * A variant's stable id within its block type, e.g. "primary": what blocks
 * store and what its class is built from ("fb-button--primary"). Renaming a
 * variant changes its name, never its slug.
 */
final readonly class VariantSlug implements \JsonSerializable, \Stringable
{
	/** Dash-separated words: no "--", which separates it in the class. */
	#[ExportToTypeScript( regExp: true )]
	public const string PATTERN = '^[a-z0-9]+(-[a-z0-9]+)*$';

	public function __construct(
		public string $value,
	) {
		if ( 1 !== preg_match( '/' . self::PATTERN . '/', $value ) ) {
			throw new \InvalidArgumentException( "Invalid variant slug \"{$value}\"." );
		}
	}

	public static function tryFrom( mixed $value ): ?self
	{
		return is_string( $value ) && 1 === preg_match( '/' . self::PATTERN . '/', $value ) ? new self( $value ) : null;
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
