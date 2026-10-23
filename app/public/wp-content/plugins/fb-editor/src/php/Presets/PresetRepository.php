<?php

declare(strict_types=1);

namespace Fb\Presets;

/**
 * Presets defined by the theme (theme.json) and the user (global styles).
 *
 * Core's built-in defaults (its 12-colour palette, gradients, font sizes…)
 * are deliberately excluded: they are never output and never offered.
 */
final class PresetRepository
{
	/** Origins we accept, later ones override earlier ones by slug. */
	private const array ORIGINS = [ 'theme', 'custom' ];

	/** @var array<string, list<Preset>> */
	private array $cache = [];

	/**
	 * @return list<Preset>
	 */
	public function all(): array
	{
		$presets = [];
		foreach ( PresetKind::cases() as $kind ) {
			array_push( $presets, ...$this->ofKind( $kind ) );
		}

		return $presets;
	}

	/**
	 * @return list<Preset>
	 */
	public function ofKind( PresetKind $kind ): array
	{
		return $this->cache[ $kind->value ] ??= $this->load( $kind );
	}

	/**
	 * @return list<Preset>
	 */
	private function load( PresetKind $kind ): array
	{
		// Boundary: core returns `array<origin, list<array<string, mixed>>>`.
		$byOrigin = wp_get_global_settings( $kind->settingsPath() );

		if ( ! is_array( $byOrigin ) ) {
			return [];
		}

		/** @var array<string, Preset> $bySlug */
		$bySlug = [];

		foreach ( self::ORIGINS as $origin ) {
			$entries = $byOrigin[ $origin ] ?? [];
			if ( ! is_array( $entries ) ) {
				continue;
			}
			foreach ( $entries as $entry ) {
				$preset = self::parse( $kind, $entry );
				if ( null !== $preset ) {
					$bySlug[ $preset->slug ] = $preset;
				}
			}
		}

		return array_values( $bySlug );
	}

	private static function parse( PresetKind $kind, mixed $entry ): ?Preset
	{
		if ( ! is_array( $entry ) ) {
			return null;
		}

		$slug  = $entry['slug'] ?? null;
		$name  = $entry['name'] ?? $slug;
		$value = $entry[ $kind->valueKey() ] ?? null;

		if ( ! is_string( $slug ) || '' === $slug || ! is_string( $name ) || ! is_string( $value ) ) {
			return null;
		}

		if ( PresetKind::FontSize === $kind ) {
			// Resolves fluid typography the same way core does. Per-preset
			// `fluid` overrides aren't passed; the theme-wide setting applies.
			$resolved = wp_get_typography_font_size_value( [ 'name' => $name, 'slug' => $slug, 'size' => $value ] );
			$value    = is_string( $resolved ) ? $resolved : $value;
		}

		$value = self::sanitizeCssValue( $value );

		return '' === $value ? null : new Preset( $kind, $slug, $name, $value );
	}

	/**
	 * Values end up inside a <style> tag: refuse anything that could close
	 * the declaration, the rule, or the tag.
	 */
	private static function sanitizeCssValue( string $value ): string
	{
		return 1 === preg_match( '/[<>{};]/', $value ) ? '' : trim( $value );
	}
}
