<?php

declare(strict_types=1);

namespace Fb\Actions\Anchor;

use Fb\TypeScript\ExportToTypeScript;

/**
 * A block's stable id for actions: what other blocks' rules point at
 * ("toggle that panel"). Given by the editor the first time the block is
 * picked as a target; the block's root gets its class.
 */
final readonly class BlockAnchor implements \JsonSerializable, \Stringable
{
	#[ExportToTypeScript( regExp: true )]
	public const string PATTERN = '^[a-z0-9]{6}$';

	/** + the anchor: the class on the block root. */
	#[ExportToTypeScript]
	public const string CLASS_PREFIX = 'fb-a-';

	public function __construct(
		public string $value,
	) {
		if ( 1 !== preg_match( '/' . self::PATTERN . '/', $value ) ) {
			throw new \InvalidArgumentException( "Invalid block anchor \"{$value}\"." );
		}
	}

	public static function tryFrom( mixed $value ): ?self
	{
		return is_string( $value ) && 1 === preg_match( '/' . self::PATTERN . '/', $value ) ? new self( $value ) : null;
	}

	/** The block's anchor, from its raw `fb` attribute; null when it has none. */
	public static function ofAttribute( mixed $attribute ): ?self
	{
		return is_array( $attribute ) || $attribute instanceof \stdClass ? self::tryFrom( ( (array) $attribute )['anchor'] ?? null ) : null;
	}

	public function className(): string
	{
		return self::CLASS_PREFIX . $this->value;
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
