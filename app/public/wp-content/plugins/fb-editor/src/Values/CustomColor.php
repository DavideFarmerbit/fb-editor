<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\TypeScript\ExportToTypeScript;

/**
 * A color picked outside the palette. Hex only (#rgb, #rgba, #rrggbb,
 * #rrggbbaa), stored lowercase: one canonical form per color.
 */
final readonly class CustomColor implements Color
{
	#[ExportToTypeScript( regExp: true )]
	public const string PATTERN = '^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$';

	public string $hex;

	public function __construct( string $hex )
	{
		if ( 1 !== preg_match( '/' . self::PATTERN . '/', $hex ) ) {
			throw new \InvalidArgumentException( "Invalid color \"{$hex}\": use a hex color." );
		}
		$this->hex = strtolower( $hex );
	}

	public function toCss(): string
	{
		return $this->hex;
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{kind: 'custom', value: string}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'custom', 'value' => $this->hex ];
	}
}
