<?php

declare(strict_types=1);

namespace Fb\Adapter;

/**
 * Every dependency on private/internal WordPress PHP APIs lives here.
 * After a WP update, if something breaks on the PHP side, start here.
 *
 * Verified against WordPress 7.1.3.
 */
final class WpInternals
{
	/**
	 * Core's slug → CSS custom property segment conversion. We must match it
	 * exactly so our preset variables are the same ones core and the editor
	 * reference (e.g. "2xl" → "2-xl").
	 */
	public static function toKebabCase( string $value ): string
	{
		return _wp_to_kebab_case( $value );
	}

	/**
	 * Core's inline-style data for a registered style handle.
	 *
	 * @return list<string>
	 */
	public static function inlineStyles( string $handle ): array
	{
		$after = wp_styles()->get_data( $handle, 'after' );

		return is_array( $after ) ? array_values( array_filter( $after, is_string( ... ) ) ) : [];
	}

	/**
	 * @param list<string> $css
	 */
	public static function replaceInlineStyles( string $handle, array $css ): void
	{
		wp_styles()->add_data( $handle, 'after', $css );
	}

	/**
	 * The file a registered style loads from, if any.
	 */
	public static function styleSource( string $handle ): ?string
	{
		$style = wp_styles()->query( $handle, 'registered' );

		return $style instanceof \_WP_Dependency && is_string( $style->src ) && '' !== $style->src ? $style->src : null;
	}

	/**
	 * Whether a style source is core's bundled block library (every core
	 * block's CSS), rather than its common CSS alone.
	 */
	public static function isBundledBlockLibrary( string $src ): bool
	{
		return 1 === preg_match( '#/css/dist/block-library/style(-rtl)?(\.min)?\.css#', $src );
	}

	/**
	 * URL of a core block's own stylesheet, as core registers it when
	 * loading block CSS separately: wp-includes/blocks/<slug>/style.css.
	 */
	public static function coreBlockStylesheetUrl( string $slug ): ?string
	{
		$file = 'blocks/' . $slug . '/style' . ( is_rtl() ? '-rtl' : '' ) . wp_scripts_get_suffix() . '.css';

		return is_readable( ABSPATH . WPINC . '/' . $file ) ? includes_url( $file ) : null;
	}
}
