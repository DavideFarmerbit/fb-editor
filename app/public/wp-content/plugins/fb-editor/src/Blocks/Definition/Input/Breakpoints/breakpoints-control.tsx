/**
 * Editor UI for BreakpointsInput, on one line: the breakpoint strip itself,
 * one toggle per breakpoint (filled: selected), then reset. Same places as
 * every other strip, so "hidden on MD" sits where MD always is.
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { describeBreakpoint } from '../../../../Breakpoints/Preview/preview';
import { BreakpointSet } from '../../../../Values/breakpoint-set';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { BreakpointsInput } from './breakpoints-input';
import './breakpoints-control.scss';

interface BreakpointsFieldProps extends ControlFieldProps {
	readonly input: BreakpointsInput;
}

export function BreakpointsField( props: BreakpointsFieldProps ): ReactElement {
	const { breakpoints, breakpoint: current, onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown instanceof BreakpointSet ? reading.shown : null;

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<span className="fb-strip fb-strip--picker" role="group" aria-label={ props.control.label }>
				{ breakpoints.map( ( breakpoint ) => {
					const picked = shown?.has( breakpoint.id ) === true;
					const classes = [ 'fb-strip__item' ];
					if ( picked ) {
						classes.push( 'is-set' );
					}
					if ( breakpoint.id === current.id ) {
						classes.push( 'is-current' );
					}
					return (
						<Button
							key={ breakpoint.id }
							className={ classes.join( ' ' ) }
							aria-pressed={ picked }
							label={
								picked
									? /* translators: %s: breakpoint description */ sprintf( __( '%s: selected', 'fb-editor' ), describeBreakpoint( breakpoint ) )
									: /* translators: %s: breakpoint description */ sprintf( __( '%s: not selected', 'fb-editor' ), describeBreakpoint( breakpoint ) )
							}
							showTooltip
							onClick={ () => onChange( ( shown ?? new BreakpointSet( [] ) ).toggled( breakpoint.id ) ) }
						>
							{ breakpoint.label }
						</Button>
					);
				} ) }
			</span>
		</Row>
	);
}
