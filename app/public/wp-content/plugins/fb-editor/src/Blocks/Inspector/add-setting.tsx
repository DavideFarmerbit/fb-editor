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
import type { BlockValues } from '../Values/block-values';

interface AddSettingProps {
	readonly definition: BlockDefinition;
	/** Controls not in the sidebar yet. */
	readonly available: readonly Control[];
	readonly resolved: ResolvedValues;
	/** Controls with a type default are marked. */
	readonly defaults: BlockValues;
	readonly onAdd: ( control: Control ) => void;
}

export function AddSetting( { definition, available, resolved, defaults, onAdd }: AddSettingProps ): ReactElement | null {
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
								let info: string | null = null;
								if ( blocked && control.visibleWhen !== null ) {
									/* translators: %s: the condition */
									info = sprintf( __( 'Needs %s', 'fb-editor' ), control.visibleWhen.describe( definition ) );
								} else if ( defaults.has( control.id ) ) {
									info = __( 'Has default', 'fb-editor' );
								}
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
