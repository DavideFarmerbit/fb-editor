<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\Presets\PresetKind;

/**
 * Reference to a theme preset. Stored as a reference, so changing the
 * theme's scale updates every block using it.
 */
final readonly class PresetLength implements Length
{
	public function __construct(
		public PresetKind $preset,
		public string $slug,
	) {
		if ( 1 !== preg_match( '/^[A-Za-z0-9_-]+$/', $slug ) ) {
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
