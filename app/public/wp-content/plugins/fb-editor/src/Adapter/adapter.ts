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
 * Block context that turns off core's own sizing UI of an image: the
 * Dimensions panel (aspect ratio, width, height, scale) and the canvas
 * resize handles. Both write core attributes (inline sizes) fb doesn't
 * manage. Core's image edit reads `context.allowResize` (galleries pass
 * false); nothing public does the same.
 */
export const NO_IMAGE_RESIZE_CONTEXT: Readonly< Record< string, unknown > > = { allowResize: false };

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

/** The canvas page's document, once loaded; null otherwise. */
export function canvasDocument(): Document | null {
	const canvas = document.querySelector< HTMLIFrameElement >( CANVAS_IFRAME )?.contentDocument ?? null;
	return canvas?.body?.classList.contains( 'editor-styles-wrapper' ) === true ? canvas : null;
}

/**
 * The clientId of the block an element of the canvas belongs to (core's
 * `data-block` on every block wrapper), or null.
 */
export function blockClientIdOf( element: Element | null ): string | null {
	return element?.closest< HTMLElement >( '[data-block]' )?.dataset.block ?? null;
}

/**
 * Calls `onDocument` with the canvas page's document, now and whenever core
 * replaces the iframe (mode or device switches), or null while there's
 * none. Returns a cleanup function.
 */
export function observeCanvasDocument( onDocument: ( canvas: Document | null ) => void ): () => void {
	let current: Document | null | undefined;

	const check = (): void => {
		const iframe = document.querySelector< HTMLIFrameElement >( CANVAS_IFRAME );
		// Until the iframe has loaded its page, its document is a blank one.
		const canvas = iframe?.contentDocument?.body?.classList.contains( 'editor-styles-wrapper' ) === true ? iframe.contentDocument : null;
		if ( canvas !== current ) {
			current = canvas;
			onDocument( canvas );
		}
	};

	check();
	const timer = window.setInterval( check, 500 );

	return () => window.clearInterval( timer );
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

/**
 * The left part of the editor header (inserter, undo/redo, list view), in
 * the post and site editors. Core offers no slot there.
 */
const HEADER_TOOLBAR = '.editor-header__toolbar';

/**
 * Calls `onNode` with an element of ours appended to the header's left
 * toolbar (for a portal), or null while there's no header. Core rebuilds
 * the header on some mode switches (site editor browse ↔ edit): a node left
 * in the old one is detected and re-created in the new one. Returns a
 * cleanup function that also removes the node.
 */
export function observeHeaderToolbar( className: string, onNode: ( node: HTMLElement | null ) => void ): () => void {
	return observeMountPoint( HEADER_TOOLBAR, className, onNode );
}

/**
 * The editor's right sidebar (block inspector, document settings, plugin
 * sidebars), in the post and site editors; same class as `$sidebar` in
 * adapter.scss. Core gives it a fixed width (CORE_SIDEBAR_WIDTH) and no way
 * to change it.
 */
const SIDEBAR = '.interface-interface-skeleton__sidebar';

/** Core's sidebar width, in px (SIDEBAR_WIDTH in ComplementaryAreaFill). */
export const CORE_SIDEBAR_WIDTH = 280;

/**
 * Calls `onNode` with an element of ours appended to the sidebar (for a
 * portal), or null while there's none. Core destroys and re-creates the
 * sidebar as it's closed and opened: the node follows. Returns a cleanup
 * function that also removes the node.
 */
export function observeSidebar( className: string, onNode: ( node: HTMLElement | null ) => void ): () => void {
	return observeMountPoint( SIDEBAR, className, onNode );
}

/** See observeHeaderToolbar and observeSidebar. */
function observeMountPoint( containerSelector: string, className: string, onNode: ( node: HTMLElement | null ) => void ): () => void {
	let node: HTMLElement | null = null;

	const attach = (): void => {
		if ( node !== null && node.isConnected ) {
			return;
		}
		const container = document.querySelector( containerSelector );
		if ( container === null ) {
			if ( node !== null ) {
				node = null;
				onNode( null );
			}
			return;
		}
		node = document.createElement( 'div' );
		node.className = className;
		container.appendChild( node );
		onNode( node );
	};

	attach();
	const timer = window.setInterval( attach, 500 );

	return () => {
		window.clearInterval( timer );
		node?.remove();
	};
}
