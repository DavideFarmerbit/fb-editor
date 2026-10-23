<?php

declare(strict_types=1);

namespace Fb\Styles;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Zero-specificity selector for a block (or one of its elements):
 *   :where(.fb-k3x9q)  or  :where(.fb-k3x9q>.fb-section__inner)
 *
 * Elements are direct children of the block root, so a nested block of the
 * same type never matches its parent's element rules.
 */
final readonly class Selector implements \Stringable
{
	#[ExportToTypeScript( regExp: true )]
	public const string CLASS_PATTERN = '^-?[_a-zA-Z][_a-zA-Z0-9-]*$';

	/**
	 * @param list<string> $path Class names, each a direct child of the previous.
	 */
	private function __construct(
		private array $path,
	) {
	}

	public static function forClass( string $className ): self
	{
		return new self( [ self::validClass( $className ) ] );
	}

	public function child( string $className ): self
	{
		return new self( [ ...$this->path, self::validClass( $className ) ] );
	}

	public function __toString(): string
	{
		return ':where(.' . implode( '>.', $this->path ) . ')';
	}

	private static function validClass( string $className ): string
	{
		if ( 1 !== preg_match( '/' . self::CLASS_PATTERN . '/', $className ) ) {
			throw new \InvalidArgumentException( "Invalid class name \"{$className}\"." );
		}

		return $className;
	}
}
