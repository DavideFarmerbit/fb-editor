/**
 * Header dropdown that picks the active breakpoint and resizes the canvas
 * to match. The canvas is an iframe, so the theme's real media queries fire.
 */
import { DropdownMenu, Fill, MenuGroup, MenuItemsChoice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
import type { ReactElement } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { desktop, mobile, tablet } from '@wordpress/icons';

import { PINNED_ITEMS_SLOT } from '../../Adapter/adapter';
import { BreakpointIcon, type Breakpoint, type BreakpointId } from '../breakpoints';
import { store } from '../../Editor/store';
import './preview.scss';

const ICONS: Record< BreakpointIcon, ReactElement > = {
	[ BreakpointIcon.Desktop ]: desktop,
	[ BreakpointIcon.Tablet ]: tablet,
	[ BreakpointIcon.Mobile ]: mobile,
};

function useCanvasWidth( width: number | null ): void {
	useEffect( () => {
		const { body } = document;

		if ( width === null ) {
			body.classList.remove( 'fb-has-canvas-width' );
			body.style.removeProperty( '--fb-canvas-width' );
			return;
		}

		body.classList.add( 'fb-has-canvas-width' );
		body.style.setProperty( '--fb-canvas-width', `${ width }px` );
	}, [ width ] );
}

function describe( breakpoint: Breakpoint ): string {
	return breakpoint.maxWidth === null
		? __( 'Base', 'fb-editor' )
		: /* translators: %d: pixel width */ sprintf( __( '≤ %dpx', 'fb-editor' ), breakpoint.maxWidth );
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

	useCanvasWidth( current.canvasWidth() );

	if ( breakpoints.size < 2 ) {
		return null;
	}

	const onSelect = ( id: BreakpointId ): void => {
		// Core's own device preview would fight ours over the canvas width.
		if ( coreDeviceType !== 'Desktop' ) {
			setDeviceType( 'Desktop' );
		}
		setBreakpoint( id );
	};

	const choices = breakpoints.map( ( breakpoint ) => ( {
		value: breakpoint.id,
		label: breakpoint.label,
		info: describe( breakpoint ),
	} ) );

	return (
		<Fill name={ PINNED_ITEMS_SLOT }>
			<DropdownMenu
				icon={ ICONS[ current.icon ?? BreakpointIcon.Desktop ] }
				label={ sprintf(
					/* translators: %s: breakpoint name */
					__( 'Breakpoint: %s', 'fb-editor' ),
					current.label
				) }
				toggleProps={ { size: 'compact' } }
				popoverProps={ { placement: 'bottom-end' } }
			>
				{ ( { onClose } ) => (
					<MenuGroup label={ __( 'Breakpoint', 'fb-editor' ) }>
						<MenuItemsChoice
							choices={ choices }
							value={ current.id }
							onSelect={ ( id ) => {
								onSelect( id as BreakpointId );
								onClose();
							} }
							onHover={ () => {} }
						/>
					</MenuGroup>
				) }
			</DropdownMenu>
		</Fill>
	);
}
