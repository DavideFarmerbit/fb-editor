<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\Presets\PresetKind;
use Fb\TypeScript\ExportToTypeScript;

/**
 * Reference to a theme preset (a spacing step, a palette color…). Stored as
 * a reference, so changing the theme updates every block using it. Which
 * preset kinds are acceptable is decided by the input.
 */
final readonly class PresetReference implements Length, Color
{
	#[ExportToTypeScript( regExp: true )]
	public const string SLUG_PATTERN = '^[A-Za-z0-9_-]+$';

	public function __construct(
		public PresetKind $preset,
		public string $slug,
	) {
		if ( 1 !== preg_match( '/' . self::SLUG_PATTERN . '/', $slug ) ) {
			throw new \InvalidArgumentException( "Invalid preset slug \"{$slug}\"." );
		}
	}

	public function toCss(): string
	{
		return 'var(' . $this->preset->variable( $this->slug ) . ')';
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{kind: 'preset', preset: string, slug: string}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'preset', 'preset' => $this->preset->value, 'slug' => $this->slug ];
	}
}
