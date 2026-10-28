/**
 * The look bar over a block's sidebar (Base · Hover · Focus · …): one row
 * that scrolls sideways when it doesn't fit. Native scrolling (trackpad
 * swipes, shift + wheel), plus dragging with the mouse; edges fade where
 * there's more; the current item is scrolled into view.
 *
 * A click picks one item; ⌘/Ctrl-click adds it to (or removes it from) the
 * current ones, for looks combining several states.
 */
import { Button } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { MouseEvent, PointerEvent, ReactElement } from 'react';

import './state-bar.scss';

export class StateBarItem {
	constructor(
		/** Stable key (a LookKey id). */
		public readonly id: string,
		public readonly label: string,
		/** Has values: filled. */
		public readonly isSet: boolean,
		/** Part of the look being edited: outlined (several, in a combined look). */
		public readonly isCurrent: boolean
	) {}
}

/** ⌘ on a Mac, Ctrl elsewhere: adds to the selection instead of replacing it. */
export function isAdditive( event: MouseEvent ): boolean {
	return event.metaKey || event.ctrlKey;
}

interface StateBarProps {
	readonly items: readonly StateBarItem[];
	/** Disabled: shown (so nothing moves), not clickable, with why. */
	readonly disabledReason: string | null;
	/** `additive`: ⌘/Ctrl-click, toggle the item within the current ones. */
	readonly onSelect: ( id: string, additive: boolean ) => void;
}

/** A drag shorter than this is still a click. */
const DRAG_THRESHOLD = 4;

export function StateBar( { items, disabledReason, onSelect }: StateBarProps ): ReactElement {
	const current = items
		.filter( ( item ) => item.isCurrent )
		.map( ( item ) => item.id )
		.join( ' ' );
	const track = useRef< HTMLDivElement >( null );
	const drag = useRef< { x: number; scroll: number; moved: boolean } | null >( null );
	const [ edges, setEdges ] = useState( { start: false, end: false } );

	const updateEdges = (): void => {
		const element = track.current;
		if ( element !== null ) {
			const start = element.scrollLeft > 0;
			const end = element.scrollLeft + element.clientWidth < element.scrollWidth - 1;
			setEdges( ( previous ) => ( previous.start === start && previous.end === end ? previous : { start, end } ) );
		}
	};

	useEffect( () => {
		const element = track.current;
		if ( element === null ) {
			return undefined;
		}
		updateEdges();
		const resizes = new ResizeObserver( updateEdges );
		resizes.observe( element );
		return () => resizes.disconnect();
	}, [ items.length ] );

	useEffect( () => {
		track.current?.querySelector( '.is-current' )?.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
	}, [ current ] );

	const onPointerDown = ( event: PointerEvent< HTMLDivElement > ): void => {
		if ( event.pointerType === 'mouse' && event.button === 0 && track.current !== null ) {
			drag.current = { x: event.clientX, scroll: track.current.scrollLeft, moved: false };
		}
	};
	const onPointerMove = ( event: PointerEvent< HTMLDivElement > ): void => {
		const state = drag.current;
		if ( state === null || track.current === null ) {
			return;
		}
		const distance = event.clientX - state.x;
		if ( ! state.moved && Math.abs( distance ) > DRAG_THRESHOLD ) {
			state.moved = true;
			track.current.setPointerCapture( event.pointerId );
		}
		if ( state.moved ) {
			track.current.scrollLeft = state.scroll - distance;
		}
	};
	const endDrag = (): void => {
		// A drag ending on an item must not also select it: cleared after
		// the click that follows the pointer up.
		window.setTimeout( () => {
			drag.current = null;
		} );
	};

	const scrollClasses = [ 'fb-state-bar__scroll' ];
	if ( edges.start ) {
		scrollClasses.push( 'has-more-start' );
	}
	if ( edges.end ) {
		scrollClasses.push( 'has-more-end' );
	}

	return (
		<div className="fb-state-bar">
			<div className={ scrollClasses.join( ' ' ) }>
			<div
				ref={ track }
				className="fb-state-bar__track"
				role="group"
				aria-label={ __( 'State', 'fb-editor' ) }
				onScroll={ updateEdges }
				onPointerDown={ onPointerDown }
				onPointerMove={ onPointerMove }
				onPointerUp={ endDrag }
				onPointerCancel={ endDrag }
			>
				{ items.map( ( item ) => {
					const itemClasses = [ 'fb-states__item' ];
					if ( item.isSet ) {
						itemClasses.push( 'is-set' );
					}
					if ( item.isCurrent ) {
						itemClasses.push( 'is-current' );
					}
					return (
						<Button
							key={ item.id }
							className={ itemClasses.join( ' ' ) }
							// Not aria-pressed: core would give it its dark pressed look.
							{ ...( item.isCurrent ? { 'aria-current': true } : {} ) }
							disabled={ disabledReason !== null }
							accessibleWhenDisabled
							{ ...( disabledReason === null ? {} : { label: disabledReason, showTooltip: true } ) }
							onClick={ ( event: MouseEvent ) => {
								if ( drag.current?.moved !== true ) {
									onSelect( item.id, isAdditive( event ) );
								}
							} }
						>
							{ item.label }
						</Button>
					);
				} ) }
			</div>
			</div>
		</div>
	);
}
