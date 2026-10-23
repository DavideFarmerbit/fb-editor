<?php

declare(strict_types=1);

namespace Fb\Presets;

/**
 * One theme.json preset, e.g. spacing "40" = 1rem.
 */
final readonly class Preset implements \JsonSerializable
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
		return $this->kind->variable( $this->slug );
	}

	public function cssReference(): string
	{
		return 'var(' . $this->cssVariable() . ')';
	}

	/**
	 * The editor gets the variable name precomputed: it never re-derives
	 * core's kebab-casing.
	 *
	 * @return array{kind: string, slug: string, name: string, value: string, variable: string}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'     => $this->kind->value,
			'slug'     => $this->slug,
			'name'     => $this->name,
			'value'    => $this->value,
			'variable' => $this->cssVariable(),
		];
	}
}
