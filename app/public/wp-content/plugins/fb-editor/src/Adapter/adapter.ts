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

/**
 * The flex container around the canvas (post and site editors); same class
 * as `$canvas-container` in adapter.scss.
 */
const CANVAS_CONTAINER = '.editor-visual-editor';

/** The space available to the canvas: the container's content box, in px. */
export class CanvasSpace {
	constructor(
		public readonly width: number,
		public readonly height: number
	) {}

	equals( other: CanvasSpace | null ): boolean {
		return other !== null && other.width === this.width && other.height === this.height;
	}
}

/**
 * Calls `onSpace` with the space available to the canvas, rounded to whole
 * pixels, now and whenever it changes. Core re-creates the container on
 * some mode switches (browse ↔ edit, code editor): the old node stays
 * around but stops resizing, so it's checked and re-attached. Returns a
 * cleanup function.
 */
export function observeCanvasSpace( onSpace: ( space: CanvasSpace | null ) => void ): () => void {
	let observed: Element | null = null;
	const resizes = new ResizeObserver( ( entries ) => {
		const box = entries[ 0 ]?.contentRect;
		if ( box !== undefined ) {
			onSpace( new CanvasSpace( Math.round( box.width ), Math.round( box.height ) ) );
		}
	} );

	const attach = (): void => {
		const current = document.querySelector( CANVAS_CONTAINER );
		if ( current === observed ) {
			return;
		}
		resizes.disconnect();
		observed = current;
		if ( current === null ) {
			onSpace( null );
			return;
		}
		resizes.observe( current );
	};

	attach();
	const timer = window.setInterval( attach, 500 );

	return () => {
		window.clearInterval( timer );
		resizes.disconnect();
	};
}
