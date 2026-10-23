<?php

declare(strict_types=1);

namespace Fb\Styles;

/**
 * Immutable, ordered set of `property: value` declarations; one value per
 * property, later writes win.
 */
final readonly class DeclarationSet implements \Countable
{
	/** Undoes this layer's value only; lower layers show through. */
	public const string REVERT = 'revert-layer';

	/**
	 * @param array<value-of<CssProperty>, string> $values Private storage.
	 */
	private function __construct(
		private array $values,
	) {
	}

	public static function empty(): self
	{
		return new self( [] );
	}

	public function with( CssProperty $property, string $value ): self
	{
		return new self( [ ...$this->values, $property->value => $value ] );
	}

	public function merge( self $other ): self
	{
		return new self( [ ...$this->values, ...$other->values ] );
	}

	public function get( CssProperty $property ): ?string
	{
		return $this->values[ $property->value ] ?? null;
	}

	public function count(): int
	{
		return count( $this->values );
	}

	public function toCss(): string
	{
		$css = '';
		foreach ( $this->values as $property => $value ) {
			$css .= "{$property}:{$value};";
		}

		return $css;
	}
}
