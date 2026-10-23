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
	 * Whether a registered style still loads from a file (core inlines small
	 * block stylesheets at `wp_head`/`wp_footer` priority 1 and clears src).
	 */
	public static function styleHasSource( string $handle ): bool
	{
		$style = wp_styles()->query( $handle, 'registered' );

		return $style instanceof \_WP_Dependency && is_string( $style->src ) && '' !== $style->src;
	}
}
