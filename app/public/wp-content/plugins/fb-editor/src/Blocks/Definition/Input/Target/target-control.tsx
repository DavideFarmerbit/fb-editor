/**
 * Editor UI for TargetInput: "This block", or another block picked in the canvas.
 * Picking: ⌖, then a click on a block in the canvas (Escape cancels); the
 * picked block gets an anchor if it has none. Hovering the chip outlines
 * the target in the canvas.
 */
import { Button } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall, pin } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { blocksWithAnchor, blockTitle, ensureAnchor, isManaged } from '../../../../Actions/Anchor/anchors';
import { blockClientIdOf, canvasBlockElement, canvasDocument } from '../../../../Adapter/adapter';
import { TargetValue } from './target-input';

/** While picking: crosshair, and the block under the pointer outlined. */
const PICKING_CSS = '[data-block]:hover{outline:2px dashed var(--wp-admin-theme-color,#3858e9)!important;outline-offset:-2px}html,[data-block]{cursor:crosshair!important}';

/** Listens to the canvas for one block click; returns a cleanup. */
function pickInCanvas( onPick: ( clientId: string ) => void, onCancel: () => void ): () => void {
	const canvas = canvasDocument();
	if ( canvas === null ) {
		onCancel();
		return () => undefined;
	}

	const style = canvas.createElement( 'style' );
	style.textContent = PICKING_CSS;
	canvas.head.appendChild( style );

	const onClick = ( event: MouseEvent ): void => {
		// The click picks: it doesn't select or edit what's under it.
		event.preventDefault();
		event.stopPropagation();
		const clientId = blockClientIdOf( event.target instanceof Element ? event.target : null );
		if ( clientId !== null && isManaged( clientId ) ) {
			onPick( clientId );
		}
	};
	const onKey = ( event: KeyboardEvent ): void => {
		if ( event.key === 'Escape' ) {
			onCancel();
		}
	};
	canvas.addEventListener( 'click', onClick, true );
	canvas.addEventListener( 'keydown', onKey, true );
	document.addEventListener( 'keydown', onKey, true );

	return () => {
		style.remove();
		canvas.removeEventListener( 'click', onClick, true );
		canvas.removeEventListener( 'keydown', onKey, true );
		document.removeEventListener( 'keydown', onKey, true );
	};
}

interface TargetFieldProps {
	readonly label: string;
	readonly value: TargetValue | null;
	/** The block the rule is on: picking it means "this block". */
	readonly clientId: string;
	readonly onChange: ( value: TargetValue | null ) => void;
}

export function TargetField( { label, value, clientId, onChange }: TargetFieldProps ): ReactElement {
	const [ picking, setPicking ] = useState( false );
	// The latest one: the rule may change while picking.
	const change = useRef( onChange );
	change.current = onChange;

	useEffect( () => {
		if ( ! picking ) {
			return undefined;
		}
		return pickInCanvas(
			( picked ) => {
				setPicking( false );
				change.current( picked === clientId ? null : new TargetValue( ensureAnchor( picked ) ) );
			},
			() => setPicking( false )
		);
	}, [ picking ] );

	const target = value?.block ?? null;
	const found = target === null ? [] : blocksWithAnchor( target );
	const name = target === null ? __( 'This block', 'fb-editor' ) : found[ 0 ] === undefined ? __( 'Missing block', 'fb-editor' ) : blockTitle( found[ 0 ] );

	// Outlines the target in the canvas while the chip is hovered.
	const highlight = ( on: boolean ): void => {
		for ( const element of found.map( canvasBlockElement ) ) {
			element?.style.setProperty( 'outline', on ? '2px solid var(--wp-admin-theme-color,#3858e9)' : '' );
		}
	};

	return (
		<div className="fb-target">
			<span className="fb-target__label">{ label }</span>
			<span
				className={ [ 'fb-target__chip', target !== null && found.length === 0 ? 'is-missing' : '' ].filter( Boolean ).join( ' ' ) }
				onMouseEnter={ () => highlight( true ) }
				onMouseLeave={ () => highlight( false ) }
			>
				{ picking ? __( 'Click a block…', 'fb-editor' ) : name }
			</span>
			<Button
				size="small"
				icon={ pin }
				label={ picking ? __( 'Cancel (Escape)', 'fb-editor' ) : __( 'Pick in the canvas', 'fb-editor' ) }
				isPressed={ picking }
				onClick={ () => setPicking( ! picking ) }
			/>
			<Button size="small" icon={ closeSmall } label={ __( 'This block', 'fb-editor' ) } disabled={ target === null } accessibleWhenDisabled onClick={ () => onChange( null ) } />
		</div>
	);
}
