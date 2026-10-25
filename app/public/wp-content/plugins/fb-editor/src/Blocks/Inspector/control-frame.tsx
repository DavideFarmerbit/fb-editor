/**
 * Frame around every control in the sidebar:
 * - header line: label, field actions, options menu ("Make default",
 *   "Remove");
 * - breakpoint line: every breakpoint (set ones filled, the inspected one
 *   outlined; click to switch);
 * - greyed out when the control has no value of its own at the inspected
 *   breakpoint (it may still inherit: editing creates an override here);
 * - inactive, with the reason, where its show-only-if condition fails.
 */
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { moreVertical, trash } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { Scope } from '../Definition/block-definition';
import { BreakpointStrip } from './breakpoint-strip';
import { ControlField, type ControlFieldProps } from './control-field';
import { FrameActionsContext } from './frame-actions';

interface ControlFrameProps extends ControlFieldProps {
	/** Why the control is inactive at this breakpoint; null when active. */
	readonly inactiveReason: string | null;
	readonly onRemove: () => void;
	/** Copies this control's values to the type default; null if not allowed. */
	readonly onMakeDefault: ( () => void ) | null;
}

export function ControlFrame( props: ControlFrameProps ): ReactElement {
	const { definition, control, values, breakpoint, breakpoints, inactiveReason, onRemove, onMakeDefault } = props;
	const [ actions, setActions ] = useState< HTMLElement | null >( null );

	const responsive = values.get( control.id );
	const isStatic = control.scope === Scope.Static;
	const touched = isStatic ? ! responsive.isEmpty() : responsive.at( breakpoint.id ) !== null;

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
				<span className="fb-control__actions" ref={ setActions } />
				<DropdownMenu
					icon={ moreVertical }
					label={ __( 'Setting options', 'fb-editor' ) }
					toggleProps={ { size: 'small' } }
					popoverProps={ { placement: 'bottom-end' } }
				>
					{ ( { onClose } ) => (
						<MenuGroup>
							{ onMakeDefault !== null && (
								<MenuItem
									info={ /* translators: %s: block type name */ sprintf( __( 'Every %s without its own value', 'fb-editor' ), definition.title ) }
									onClick={ () => {
										onMakeDefault();
										onClose();
									} }
								>
									{ /* translators: %s: block type name */ sprintf( __( 'Make %s default', 'fb-editor' ), definition.title ) }
								</MenuItem>
							) }
							<MenuItem
								icon={ trash }
								isDestructive
								onClick={ () => {
									onRemove();
									onClose();
								} }
							>
								{ __( 'Remove setting', 'fb-editor' ) }
							</MenuItem>
						</MenuGroup>
					) }
				</DropdownMenu>
			</div>
			{ isStatic ? (
				<span className={ touched ? 'fb-strip is-static is-set' : 'fb-strip is-static' }>{ __( 'All breakpoints', 'fb-editor' ) }</span>
			) : (
				<BreakpointStrip breakpoints={ breakpoints } current={ breakpoint } setAt={ responsive.setAt( breakpoints ) } />
			) }
			{ inactiveReason !== null && (
				<p className="fb-control__inactive">
					{ /* translators: %s: the condition, e.g. "Display: Flex or Grid" */ sprintf( __( 'Inactive here: needs %s', 'fb-editor' ), inactiveReason ) }
				</p>
			) }
			<div className="fb-control__body">
				<FrameActionsContext.Provider value={ actions }>
					<ControlField { ...props } />
				</FrameActionsContext.Provider>
			</div>
		</div>
	);
}
