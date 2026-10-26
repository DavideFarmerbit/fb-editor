/**
 * Every dependency on WordPress internals lives here (and in adapter.scss,
 * and Fb\Adapter\WpInternals on the PHP side).
 * After a WP update, if something in the editor UI breaks, start here.
 *
 * Verified against WordPress 7.1.3.
 */

/**
 * Slot name behind the (private since 7.x) `PinnedItems` component that
 * renders on the right side of the editor header, in both the post and
 * site editors. A public `<Fill name={ PINNED_ITEMS_SLOT }>` targets it.
 */
export const PINNED_ITEMS_SLOT = 'PinnedItems/core';

/**
 * The editor canvas iframe (post and site editors). Core's own code finds
 * it by this name.
 */
const CANVAS_IFRAME = 'iframe[name="editor-canvas"]';

/**
 * The canvas DOM element of a block. Core marks every block wrapper with
 * `data-block="<clientId>"`; its own hook for this (useBlockElement) is a
 * private API. Null until the block is mounted, or outside the canvas.
 */
export function canvasBlockElement( clientId: string ): HTMLElement | null {
	const iframe = document.querySelector< HTMLIFrameElement >( CANVAS_IFRAME );
	const canvas = iframe?.contentDocument ?? document;
	return canvas.querySelector< HTMLElement >( `[data-block="${ CSS.escape( clientId ) }"]` );
}
