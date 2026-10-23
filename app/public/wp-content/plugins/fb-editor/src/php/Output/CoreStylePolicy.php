<?php

declare(strict_types=1);

namespace Fb\Output;

use Fb\CoreBlocks\ManagedCoreBlock;

/**
 * Decides what happens to each core stylesheet on the frontend.
 *
 * - Core-wide noise (block library, global styles, block supports, emoji)
 *   is removed: fb prints its own tokens and block CSS.
 * - Stylesheets of core blocks fb manages are removed: their styling is ours.
 * - Every other core stylesheet is demoted into the `wp` layer, so it still
 *   works but can never beat fb or site CSS.
 * - Non-core handles (other plugins) are left alone.
 */
final readonly class CoreStylePolicy
{
	private const array REMOVED_HANDLES = [
		'wp-block-library',
		'wp-block-library-theme',
		'classic-theme-styles',
		'global-styles',
		'core-block-supports',
		'core-block-supports-duotone',
		'wp-emoji-styles',
	];

	private const array LAYERED_HANDLES = [
		'wp-img-auto-sizes-contain',
		'wp-block-template-skip-link',
	];

	public function decide( string $handle ): StyleAction
	{
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
