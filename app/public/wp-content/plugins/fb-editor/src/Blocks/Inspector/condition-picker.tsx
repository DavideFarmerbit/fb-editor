/**
 * Which media conditions the edited look requires ("Always", "Dark mode",
 * "Dark mode + Touch"…), at the end of the state bar. A click picks one
 * condition; ⌘/Ctrl-click adds or removes one (the menu stays open).
 * Conditions with values (in a look of the same part) are marked.
 */
import { Button, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { check, chevronDown } from '@wordpress/icons';
import type { MouseEvent, ReactElement } from 'react';

import type { BlockDefinition } from '../Definition/block-definition';
import type { MediaCondition } from '../Looks/look-key';
import { isAdditive } from './state-bar';
import './condition-picker.scss';

interface ConditionPickerProps {
	readonly definition: BlockDefinition;
	/** The edited look's conditions. */
	readonly current: readonly MediaCondition[];
	/** Whether some look requiring this condition has values. */
	readonly isSet: ( condition: MediaCondition ) => boolean;
	readonly disabled: boolean;
	readonly onChange: ( conditions: MediaCondition[] ) => void;
}

export function ConditionPicker( { definition, current, isSet, disabled, onChange }: ConditionPickerProps ): ReactElement {
	const label =
		current.length === 0
			? __( 'Always', 'fb-editor' )
			: current.map( ( condition ) => definition.conditions.find( ( info ) => info.id === condition )?.label ?? condition ).join( ' + ' );
	const anySet = definition.conditions.some( ( info ) => isSet( info.id ) );

	return (
		<Dropdown
			className="fb-condition-picker"
			popoverProps={ { placement: 'bottom-end' } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					className={ [ 'fb-condition-picker__toggle', current.length > 0 ? 'is-active' : '', anySet ? 'has-set' : '' ].filter( Boolean ).join( ' ' ) }
					icon={ chevronDown }
					iconPosition="right"
					aria-expanded={ isOpen }
					disabled={ disabled }
					accessibleWhenDisabled
					label={ __( 'When', 'fb-editor' ) }
					showTooltip
					onClick={ onToggle }
				>
					{ label }
				</Button>
			) }
			renderContent={ ( { onClose } ) => (
				<MenuGroup label={ __( 'When (⌘-click: combine)', 'fb-editor' ) }>
					<MenuItem
						icon={ current.length === 0 ? check : null }
						onClick={ () => {
							onChange( [] );
							onClose();
						} }
					>
						{ __( 'Always', 'fb-editor' ) }
					</MenuItem>
					{ definition.conditions.map( ( info ) => {
						const selected = current.includes( info.id );
						return (
							<MenuItem
								key={ info.id }
								icon={ selected ? check : null }
								className={ isSet( info.id ) ? 'fb-condition-picker__item is-set' : 'fb-condition-picker__item' }
								onClick={ ( event: MouseEvent ) => {
									if ( isAdditive( event ) ) {
										onChange( selected ? current.filter( ( condition ) => condition !== info.id ) : [ ...current, info.id ] );
										return;
									}
									onChange( [ info.id ] );
									onClose();
								} }
							>
								{ info.label }
							</MenuItem>
						);
					} ) }
				</MenuGroup>
			) }
		/>
	);
}
