/**
 * Frame around every control in the sidebar:
 * - label + one badge per breakpoint that has its own value (the inspected
 *   one highlighted; clicking a badge switches to that breakpoint);
 * - "Remove" (clears the control at every breakpoint);
 * - greyed out when the control has no value of its own at the inspected
 *   breakpoint (it may still inherit: editing creates an override here);
 * - inactive, with the reason, where its show-only-if condition fails.
 */
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { trash } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { describeBreakpoint } from '../../Breakpoints/Preview/preview';
import { store } from '../../Editor/store';
import { Scope } from '../Definition/block-definition';
import { ControlField, type ControlFieldProps } from './control-field';

interface ControlFrameProps extends ControlFieldProps {
	/** Why the control is inactive at this breakpoint; null when active. */
	readonly inactiveReason: string | null;
	readonly onRemove: () => void;
}

export function ControlFrame( props: ControlFrameProps ): ReactElement {
	const { control, values, breakpoint, breakpoints, inactiveReason, onRemove } = props;
	const { setBreakpoint } = useDispatch( store );

	const responsive = values.get( control.id );
	const isStatic = control.scope === Scope.Static;
	const touched = isStatic ? ! responsive.isEmpty() : responsive.at( breakpoint.id ) !== null;
	const setAt = isStatic ? [] : responsive.setAt( breakpoints );

	const classes = [ 'fb-control' ];
	if ( ! touched ) {
		classes.push( 'is-untouched' );
	}
	if ( inactiveReason !== null ) {
		classes.push( 'is-inactive' );
	}

	return (
		<div className={ classes.join( ' ' ) }>
			<div className="fb-control__header">
				<span className="fb-control__label">{ control.label }</span>
				<span className="fb-control__badges">
					{ isStatic && touched && <span className="fb-control__badge is-static">{ __( 'All', 'fb-editor' ) }</span> }
					{ setAt.map( ( id ) => {
						const target = breakpoints.get( id );
						return target === undefined ? null : (
							<Button
								key={ id }
								size="small"
								className="fb-control__badge"
								isPressed={ id === breakpoint.id }
								label={
									/* translators: %s: breakpoint description */
									sprintf( __( 'Set at %s: switch to it', 'fb-editor' ), describeBreakpoint( target ) )
								}
								showTooltip
								onClick={ () => setBreakpoint( id ) }
							>
								{ target.label }
							</Button>
						);
					} ) }
				</span>
				<Button
					size="small"
					icon={ trash }
					label={ __( 'Remove setting', 'fb-editor' ) }
					className="fb-control__remove"
					onClick={ onRemove }
				/>
			</div>
			{ inactiveReason !== null && (
				<p className="fb-control__inactive">
					{ /* translators: %s: the condition, e.g. "Display: Flex or Grid" */ sprintf( __( 'Inactive here: needs %s', 'fb-editor' ), inactiveReason ) }
				</p>
			) }
			<div className="fb-control__body">
				<ControlField { ...props } />
			</div>
		</div>
	);
}
