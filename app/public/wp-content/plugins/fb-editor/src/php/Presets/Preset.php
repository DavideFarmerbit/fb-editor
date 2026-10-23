<?php

declare(strict_types=1);

namespace Fb\Presets;

use Fb\Adapter\WpInternals;

/**
 * One theme.json preset, e.g. spacing "40" = 1rem.
 */
final readonly class Preset
{
	public function __construct(
		public PresetKind $kind,
		public string $slug,
		public string $name,
		public string $value,
	) {
	}

	/**
	 * Same name core uses, so `var(--wp--preset--spacing--40)` stays valid.
	 */
	public function cssVariable(): string
	{
		return '--wp--preset--' . $this->kind->value . '--' . WpInternals::toKebabCase( $this->slug );
	}

	public function cssReference(): string
	{
		return 'var(' . $this->cssVariable() . ')';
	}
}
