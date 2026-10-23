<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A single CSS keyword (e.g. "flex", "center"). Which keywords are allowed
 * is decided by the input that produces it; this only guarantees it's a
 * syntactically safe keyword.
 */
final readonly class KeywordValue implements CssValue, ResponsiveValue
{
	public function __construct(
		public string $keyword,
	) {
		if ( 1 !== preg_match( '/^[a-z][a-z0-9-]*$/', $keyword ) ) {
			throw new \InvalidArgumentException( "Invalid CSS keyword \"{$keyword}\"." );
		}
	}

	public static function of( \BackedEnum $case ): self
	{
		return new self( (string) $case->value );
	}

	public function toCss(): string
	{
		return $this->keyword;
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	public function jsonSerialize(): string
	{
		return $this->keyword;
	}
}
