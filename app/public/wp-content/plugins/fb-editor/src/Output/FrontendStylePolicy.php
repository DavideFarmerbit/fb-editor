<?php

declare(strict_types=1);

namespace Fb\Output;

use Fb\CoreBlocks\ManagedCoreBlock;

/**
 * Decides what happens to each core stylesheet on the frontend.
 *
 * - Core-wide noise (block library, presets, emoji) is removed: fb prints
 *   its own tokens, and core's layout rules itself (see TokenStylesheet).
 * - Stylesheets of core blocks fb manages are removed: their styling is ours.
 * - Every other core stylesheet, including per-instance block-supports CSS
 *   (`wp-container-*` rules) unmanaged blocks rely on, is demoted into the
 *   `wp` layer, so it still works but can never beat fb or site CSS.
 * - Non-core handles (other plugins) are left alone.
 */
final readonly class FrontendStylePolicy implements StylePolicy
{
	private const array REMOVED_HANDLES = [
		'wp-block-library',
		'wp-block-library-theme',
		'classic-theme-styles',
		'global-styles',
		'wp-emoji-styles',
	];

	private const array LAYERED_HANDLES = [
		'core-block-supports',
		'core-block-supports-duotone',
		'wp-img-auto-sizes-contain',
		'wp-block-template-skip-link',
	];

	public function decide( string $handle ): StyleAction
	{
		// The frontend loads block CSS per block (never the bundle), so the
		// library handle is only common CSS: removed above.
		if ( in_array( $handle, self::REMOVED_HANDLES, true ) ) {
			return StyleAction::Remove;
		}

		if ( in_array( $handle, self::LAYERED_HANDLES, true ) ) {
			return StyleAction::Layer;
		}

		if ( str_starts_with( $handle, 'wp-block-' ) ) {
			return null === ManagedCoreBlock::fromStyleHandle( $handle ) ? StyleAction::Layer : StyleAction::Remove;
		}

		return StyleAction::Keep;
	}
}
