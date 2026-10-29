/**
 * The editor's right sidebar, resizable sideways: a handle on its inner
 * edge, dragged or moved with the keyboard (arrows, Shift for bigger steps,
 * Home / End); a double click goes back to core's width. Every sidebar
 * shown there takes the width, ours included.
 *
 * The width is a user preference (core/preferences, saved in the user's
 * profile): it follows them from post to post. While dragging it's only
 * shown, and saved once at the end.
 *
 * Below 782px core opens the sidebar full screen: no handle, no width
 * (sidebar-width.scss).
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { createPortal, useEffect, useState } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';
import { store as preferencesStore } from '@wordpress/preferences';
import type { ReactElement } from 'react';

import { CORE_SIDEBAR_WIDTH, observeSidebar } from '../../Adapter/adapter';
import './sidebar-width.scss';

const PREFERENCE_SCOPE = 'fb-editor';
const PREFERENCE_NAME = 'sidebarWidth';

/** Core's panels are laid out for its width: never narrower. */
const MIN_WIDTH = CORE_SIDEBAR_WIDTH;

/** Share of the window the sidebar can take, the rest stays the canvas's. */
const MAX_SHARE = 0.5;

const KEY_STEP = 16;
const LARGE_KEY_STEP = 64;

function maxWidth(): number {
	return Math.max( MIN_WIDTH, Math.round( window.innerWidth * MAX_SHARE ) );
}

function clampWidth( width: number ): number {
	return Math.min( maxWidth(), Math.max( MIN_WIDTH, Math.round( width ) ) );
}

/** The saved preference, or null for core's width (anything malformed too). */
function savedWidth( value: unknown ): number | null {
	return typeof value === 'number' && Number.isFinite( value ) ? value : null;
}

/**
 * The width once the edge has moved by `deltaX` px. The sidebar is on the
 * right (on the left in RTL languages): toward the canvas widens it.
 */
function movedWidth( width: number, deltaX: number ): number {
	return clampWidth( width + ( isRTL() ? deltaX : -deltaX ) );
}

/** The width a key moves the edge to, or null for other keys. */
function keyWidth( key: string, width: number, step: number ): number | null {
	switch ( key ) {
		case 'ArrowLeft':
			return movedWidth( width, -step );
		case 'ArrowRight':
			return movedWidth( width, step );
		case 'Home':
			return MIN_WIDTH;
		case 'End':
			return maxWidth();
		default:
			return null;
	}
}

/** Where a drag started. */
class DragStart {
	constructor(
		public readonly pointerX: number,
		public readonly width: number
	) {}
}

function applyWidth( width: number | null ): void {
	const { body } = document;
	body.classList.toggle( 'fb-sidebar-sized', width !== null );
	if ( width === null ) {
		body.style.removeProperty( '--fb-sidebar-width' );
		body.style.removeProperty( '--fb-sidebar-max-width' );
		return;
	}
	body.style.setProperty( '--fb-sidebar-width', `${ width }px` );
	// The window may shrink after the width is chosen.
	body.style.setProperty( '--fb-sidebar-max-width', `${ MAX_SHARE * 100 }vw` );
}

export default function SidebarWidth(): ReactElement | null {
	const [ mount, setMount ] = useState< HTMLElement | null >( null );
	useEffect( () => observeSidebar( 'fb-sidebar-width', setMount ), [] );

	const saved = useSelect( ( select ) => savedWidth( select( preferencesStore ).get( PREFERENCE_SCOPE, PREFERENCE_NAME ) ), [] );
	const { set } = useDispatch( preferencesStore );

	// Shown while dragging or a key is held, not saved yet.
	const [ draft, setDraft ] = useState< number | null >( null );
	const [ drag, setDrag ] = useState< DragStart | null >( null );

	const width = draft ?? saved;
	useEffect( () => {
		applyWidth( width );
		return () => applyWidth( null );
	}, [ width ] );

	useEffect( () => {
		document.body.classList.toggle( 'fb-sidebar-resizing', drag !== null );
		return () => document.body.classList.remove( 'fb-sidebar-resizing' );
	}, [ drag ] );

	if ( mount === null ) {
		return null;
	}

	const current = clampWidth( width ?? MIN_WIDTH );

	const save = (): void => {
		if ( draft !== null ) {
			set( PREFERENCE_SCOPE, PREFERENCE_NAME, draft );
			setDraft( null );
		}
	};

	return createPortal(
		// A focusable separator is a widget (WAI-ARIA "window splitter"),
		// which jsx-a11y doesn't know.
		// eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions
		<div
			className="fb-sidebar-width__handle"
			role="separator"
			aria-orientation="vertical"
			aria-label={ __( 'Sidebar width', 'fb-editor' ) }
			aria-valuemin={ MIN_WIDTH }
			aria-valuemax={ maxWidth() }
			aria-valuenow={ current }
			title={ __( 'Drag to resize the sidebar. Double click: default width.', 'fb-editor' ) }
			tabIndex={ 0 }
			onPointerDown={ ( event ) => {
				if ( event.button !== 0 ) {
					return;
				}
				// No text selection while dragging.
				event.preventDefault();
				// Keeps the moves coming while the pointer is over the canvas iframe.
				event.currentTarget.setPointerCapture( event.pointerId );
				setDrag( new DragStart( event.clientX, current ) );
			} }
			onPointerMove={ ( event ) => {
				if ( drag !== null ) {
					setDraft( movedWidth( drag.width, event.clientX - drag.pointerX ) );
				}
			} }
			// After pointerup and pointercancel alike.
			onLostPointerCapture={ () => {
				setDrag( null );
				save();
			} }
			onDoubleClick={ () => {
				setDraft( null );
				set( PREFERENCE_SCOPE, PREFERENCE_NAME, null );
			} }
			onKeyDown={ ( event ) => {
				const next = keyWidth( event.key, current, event.shiftKey ? LARGE_KEY_STEP : KEY_STEP );
				if ( next !== null ) {
					event.preventDefault();
					setDraft( next );
				}
			} }
			onKeyUp={ save }
			onBlur={ save }
		/>,
		mount
	);
}
