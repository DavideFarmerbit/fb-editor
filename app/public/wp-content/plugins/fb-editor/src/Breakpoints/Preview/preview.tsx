/**
 * Top-bar breakpoint switcher: one button per breakpoint (XXL … XS),
 * exactly one active. Switching resizes the canvas to that breakpoint, so
 * the theme's real media queries fire inside the iframe.
 */
import { Button, Fill } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { observeCanvasSpace, PINNED_ITEMS_SLOT, type CanvasSpace } from '../../Adapter/adapter';
import { store } from '../../Editor/store';
import type { Breakpoint } from '../breakpoints';
import './preview.scss';

/** Smallest scale applied: a guard for a (nearly) collapsed editor area. */
const MIN_SCALE = 0.1;

/**
 * Fixes the canvas to the breakpoint's preview width, for every breakpoint
 * (the base too): media queries inside it must never depend on how wide
 * the editor window happens to be.
 *
 * When that's wider than the space available, the iframe is scaled down to
 * fit with a transform: drawn smaller, but inside it's still exactly
 * `width` wide (same wrapping, same media queries). Only the iframe's own
 * wrapper is scaled, never the box around it, which also holds core's
 * popover layer: scaled, toolbars and the insertion point would be placed
 * in shrunken coordinates (see preview.scss). Returns the scale, at most 1.
 */
function useCanvasWidth( width: number ): number {
	const [ space, setSpace ] = useState< CanvasSpace | null >( null );
	useEffect( () => observeCanvasSpace( ( next ) => setSpace( ( previous ) => ( next?.equals( previous ) === true ? previous : next ) ) ), [] );

	const scale = space === null || space.width <= 0 ? 1 : Math.max( MIN_SCALE, Math.min( 1, space.width / width ) );

	useEffect( () => {
		const { body } = document;
		body.classList.add( 'fb-has-canvas-width' );
		body.classList.toggle( 'fb-canvas-measured', space !== null );
		body.classList.toggle( 'fb-canvas-scaled', space !== null && scale < 1 );
		body.style.setProperty( '--fb-canvas-width', `${ width }px` );
		body.style.setProperty( '--fb-canvas-scale', String( scale ) );
		body.style.setProperty( '--fb-canvas-height', `${ space?.height ?? 0 }px` );
	}, [ width, scale, space ] );

	return scale;
}

/** e.g. "LG · 992–1199px", "XXL · ≥ 1400px", "XS · ≤ 575px". */
export function describeBreakpoint( breakpoint: Breakpoint ): string {
	const { label, minWidth, maxWidth } = breakpoint;

	if ( maxWidth === null ) {
		return minWidth === null
			? label
			: /* translators: 1: breakpoint label, 2: min width */ sprintf( __( '%1$s · ≥ %2$dpx', 'fb-editor' ), label, minWidth );
	}
	if ( minWidth === null ) {
		return /* translators: 1: breakpoint label, 2: max width */ sprintf( __( '%1$s · ≤ %2$dpx', 'fb-editor' ), label, maxWidth );
	}
	return /* translators: 1: breakpoint label, 2: min width, 3: max width */ sprintf(
		__( '%1$s · %2$d–%3$dpx', 'fb-editor' ),
		label,
		minWidth,
		maxWidth
	);
}

export default function BreakpointPreview(): ReactElement | null {
	const { breakpoints, current, coreDeviceType } = useSelect(
		( select ) => ( {
			breakpoints: select( store ).getBreakpoints(),
			current: select( store ).getBreakpoint(),
			coreDeviceType: select( editorStore ).getDeviceType(),
		} ),
		[]
	);
	const { setBreakpoint } = useDispatch( store );
	const { setDeviceType } = useDispatch( editorStore );

	const scale = useCanvasWidth( current.previewWidth );

	if ( breakpoints.size < 2 ) {
		return null;
	}

	return (
		<Fill name={ PINNED_ITEMS_SLOT }>
			<div className="fb-breakpoints" role="group" aria-label={ __( 'Breakpoint', 'fb-editor' ) }>
				{ breakpoints.map( ( breakpoint ) => (
					<Button
						key={ breakpoint.id }
						size="compact"
						className="fb-breakpoints__button"
						isPressed={ breakpoint.id === current.id }
						label={ describeBreakpoint( breakpoint ) }
						showTooltip
						onClick={ () => {
							// Core's own device preview would fight ours over the canvas width.
							if ( coreDeviceType !== 'Desktop' ) {
								setDeviceType( 'Desktop' );
							}
							setBreakpoint( breakpoint.id );
						} }
					>
						{ breakpoint.label }
					</Button>
				) ) }
				{ scale < 1 && (
					<span
						className="fb-breakpoints__scale"
						title={ /* translators: %d: preview width in pixels */ sprintf( __( 'Scaled down to fit: the page is still %dpx wide', 'fb-editor' ), current.previewWidth ) }
					>
						{ `${ Math.round( scale * 100 ) }%` }
					</span>
				) }
			</div>
		</Fill>
	);
}
