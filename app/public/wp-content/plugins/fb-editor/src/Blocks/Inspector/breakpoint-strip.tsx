/**
 * Every breakpoint, always, in the same place on every control: filled
 * where the control has a value, outlined for the inspected breakpoint,
 * faint elsewhere. Clicking one switches to it. Position, not text, tells
 * what's set: checking becomes muscle memory.
 */
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import type { Breakpoint, BreakpointId, BreakpointList } from '../../Breakpoints/breakpoints';
import { describeBreakpoint } from '../../Breakpoints/Preview/preview';
import { store } from '../../Editor/store';

interface BreakpointStripProps {
	readonly breakpoints: BreakpointList;
	readonly current: Breakpoint;
	/** Breakpoints with a value of their own. */
	readonly setAt: readonly BreakpointId[];
}

export function BreakpointStrip( { breakpoints, current, setAt }: BreakpointStripProps ): ReactElement {
	const { setBreakpoint } = useDispatch( store );

	return (
		<span className="fb-strip" role="group" aria-label={ __( 'Breakpoints', 'fb-editor' ) }>
			{ breakpoints.map( ( breakpoint ) => {
				const isSet = setAt.includes( breakpoint.id );
				const classes = [ 'fb-strip__item' ];
				if ( isSet ) {
					classes.push( 'is-set' );
				}
				if ( breakpoint.id === current.id ) {
					classes.push( 'is-current' );
				}
				return (
					<Button
						key={ breakpoint.id }
						className={ classes.join( ' ' ) }
						label={
							isSet
								? /* translators: %s: breakpoint description */ sprintf( __( '%s: set', 'fb-editor' ), describeBreakpoint( breakpoint ) )
								: /* translators: %s: breakpoint description */ sprintf( __( '%s: not set', 'fb-editor' ), describeBreakpoint( breakpoint ) )
						}
						showTooltip
						onClick={ () => setBreakpoint( breakpoint.id ) }
					>
						{ breakpoint.label }
					</Button>
				);
			} ) }
		</span>
	);
}
