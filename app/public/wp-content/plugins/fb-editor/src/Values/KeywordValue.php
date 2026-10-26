<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\TypeScript\ExportToTypeScript;

/**
 * A single CSS keyword (e.g. "flex", "center"). Which keywords are allowed
 * is decided by the input that produces it; this only guarantees it's a
 * syntactically safe keyword.
 */
final readonly class KeywordValue implements CssValue, ResponsiveValue
{
	#[ExportToTypeScript( regExp: true )]
	/** Letters, digits and dashes: "flex", "space-between", "700". */
	public const string PATTERN = '^[a-z0-9][a-z0-9-]*$';

	public function __construct(
		public string $keyword,
	) {
		if ( 1 !== preg_match( '/' . self::PATTERN . '/', $keyword ) ) {
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
