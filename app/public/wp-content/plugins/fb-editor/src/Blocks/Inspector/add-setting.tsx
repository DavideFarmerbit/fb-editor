/**
 * "Add setting" menu: the block's controls not shown yet, grouped by panel.
 * A control whose condition fails at the inspected breakpoint is listed
 * disabled, with what it needs.
 */
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import type { ReactElement } from 'react';

import type { BlockDefinition, Control } from '../Definition/block-definition';
import type { ResolvedValues } from '../Definition/Condition/conditions';

interface AddSettingProps {
	readonly definition: BlockDefinition;
	/** Controls not in the sidebar yet. */
	readonly available: readonly Control[];
	readonly resolved: ResolvedValues;
	readonly onAdd: ( control: Control ) => void;
}

export function AddSetting( { definition, available, resolved, onAdd }: AddSettingProps ): ReactElement | null {
	if ( available.length === 0 ) {
		return null;
	}

	return (
		<DropdownMenu
			icon={ plus }
			label={ __( 'Add setting', 'fb-editor' ) }
			text={ __( 'Add setting', 'fb-editor' ) }
			className="fb-inspector__add"
			toggleProps={ { size: 'compact', variant: 'secondary' } }
			popoverProps={ { placement: 'bottom-start' } }
		>
			{ ( { onClose } ) =>
				definition.layout.map( ( panel ) => {
					const controls = available.filter( ( control ) => panel.controls.includes( control.id ) );
					if ( controls.length === 0 ) {
						return null;
					}
					return (
						<MenuGroup key={ panel.label } label={ panel.label }>
							{ controls.map( ( control ) => {
								const blocked = control.visibleWhen !== null && ! control.visibleWhen.evaluate( resolved );
								const info =
									blocked && control.visibleWhen !== null
										? /* translators: %s: the condition */ sprintf( __( 'Needs %s', 'fb-editor' ), control.visibleWhen.describe( definition ) )
										: null;
								return (
									<MenuItem
										key={ control.id }
										disabled={ blocked }
										{ ...( info === null ? {} : { info } ) }
										onClick={ () => {
											onAdd( control );
											onClose();
										} }
									>
										{ control.label }
									</MenuItem>
								);
							} ) }
						</MenuGroup>
					);
				} )
			}
		</DropdownMenu>
	);
}
