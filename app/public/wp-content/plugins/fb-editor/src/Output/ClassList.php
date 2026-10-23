<?php

declare(strict_types=1);

namespace Fb\Output;

/**
 * Immutable, ordered, de-duplicated set of HTML class names.
 */
final readonly class ClassList implements \Stringable
{
	/** @var list<non-empty-string> */
	public array $names;

	public function __construct( string ...$names )
	{
		$unique = [];
		foreach ( $names as $name ) {
			if ( '' !== $name && ! in_array( $name, $unique, true ) ) {
				$unique[] = $name;
			}
		}
		$this->names = $unique;
	}

	public static function parse( string $classAttribute ): self
	{
		$names = preg_split( '/\s+/', trim( $classAttribute ) );

		return new self( ...( false === $names ? [] : $names ) );
	}

	public function with( self $other ): self
	{
		return new self( ...$this->names, ...$other->names );
	}

	public function contains( string $name ): bool
	{
		return in_array( $name, $this->names, true );
	}

	public function isEmpty(): bool
	{
		return [] === $this->names;
	}

	public function __toString(): string
	{
		return implode( ' ', $this->names );
	}
}
