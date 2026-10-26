/**
 * Top-bar breakpoint switcher: one button per breakpoint (XXL … XS),
 * exactly one active. Switching resizes the canvas to that breakpoint, so
 * the theme's real media queries fire inside the iframe.
 */
import { Button, Fill } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { PINNED_ITEMS_SLOT } from '../../Adapter/adapter';
import { store } from '../../Editor/store';
import type { Breakpoint } from '../breakpoints';
import './preview.scss';

/**
 * Fixes the canvas to the breakpoint's preview width, for every breakpoint
 * (the base too): media queries inside it must never depend on how wide
 * the editor window happens to be.
 */
function useCanvasWidth( width: number ): void {
	useEffect( () => {
		const { body } = document;
		body.classList.add( 'fb-has-canvas-width' );
		body.style.setProperty( '--fb-canvas-width', `${ width }px` );
	}, [ width ] );
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

	useCanvasWidth( current.previewWidth );

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
			</div>
		</Fill>
	);
}
