<?php

declare(strict_types=1);

namespace Fb\Output;

use Fb\CoreBlocks\ManagedCoreBlock;

/**
 * Decides what happens to each stylesheet inside the editor canvas iframe.
 *
 * Mirrors FrontendStylePolicy for content CSS, so the canvas renders like
 * the frontend, but never touches editor UI CSS (selection outlines,
 * appenders, placeholders…): that stays unlayered so it always draws on
 * top of block styling.
 */
final readonly class CanvasStylePolicy implements StylePolicy
{
	/** Editor UI stylesheets that aren't recognisable by the `-editor` suffix. */
	private const array EDITOR_UI_HANDLES = [
		'wp-edit-blocks',
		'wp-block-editor',
		'wp-block-editor-content',
		'wp-components',
		'wp-base-styles',
		'wp-theme',
	];

	public function decide( string $handle ): StyleAction
	{
		if ( in_array( $handle, self::EDITOR_UI_HANDLES, true ) || str_ends_with( $handle, '-editor' ) ) {
			return StyleAction::Keep;
		}

		// In wp-admin it bundles every core block's CSS, managed ones included
		// (button padding, radius…): keep only what the frontend would load.
		if ( 'wp-block-library' === $handle ) {
			return StyleAction::Unbundle;
		}

		if ( str_starts_with( $handle, 'wp-block-' ) ) {
			return null === ManagedCoreBlock::fromStyleHandle( $handle ) ? StyleAction::Layer : StyleAction::Remove;
		}

		return StyleAction::Keep;
	}
}
