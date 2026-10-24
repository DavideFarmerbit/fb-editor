/**
 * The block sidebar, generated from the PHP definition. Starts empty: a
 * control shows once it has a value somewhere, or once it's added from the
 * "Add setting" menu (kept for the session even while empty). Panels show
 * only when they hold a shown control.
 */
import { PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import type { ReactElement } from 'react';

import { store } from '../../Editor/store';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import type { BlockValues } from '../Values/block-values';
import { AddSetting } from './add-setting';
import { ControlFrame } from './control-frame';
import './inspector.scss';

interface InspectorProps {
	readonly definition: BlockDefinition;
	readonly values: BlockValues;
	readonly clientId: string;
	readonly onChange: ( values: BlockValues ) => void;
}

export function Inspector( { definition, values, clientId, onChange }: InspectorProps ): ReactElement {
	const { breakpoint, breakpoints, added } = useSelect(
		( select ) => ( {
			breakpoint: select( store ).getBreakpoint(),
			breakpoints: select( store ).getBreakpoints(),
			added: select( store ).getAddedControls( clientId ),
		} ),
		[ clientId ]
	);
	const { addControl, forgetControl } = useDispatch( store );
	const resolved = values.resolvedAt( breakpoint.id );

	const isShown = ( control: Control ): boolean => values.has( control.id ) || added.includes( control.id );
	const available = definition.controls.filter( ( control ) => ! isShown( control ) );

	return (
		<div className="fb-inspector">
			<div className="fb-inspector__toolbar">
				<AddSetting
					definition={ definition }
					available={ available }
					resolved={ resolved }
					onAdd={ ( control ) => addControl( clientId, control.id ) }
				/>
			</div>
			{ definition.layout.map( ( panel ) => {
				const controls = panel.controls
					.map( ( id ) => definition.control( id ) )
					.filter( ( control ): control is Control => control !== undefined && isShown( control ) );

				if ( controls.length === 0 ) {
					return null;
				}

				return (
					<PanelBody key={ panel.label } title={ panel.label }>
						{ controls.map( ( control ) => (
							<ControlFrame
								key={ control.id }
								definition={ definition }
								control={ control }
								values={ values }
								breakpoint={ breakpoint }
								breakpoints={ breakpoints }
								inactiveReason={
									control.visibleWhen !== null && ! control.visibleWhen.evaluate( resolved )
										? control.visibleWhen.describe( definition )
										: null
								}
								onChange={ ( value ) => {
									// Editing keeps the control in the sidebar even if a
									// reset later empties it.
									addControl( clientId, control.id );
									onChange( values.with( control, breakpoint.id, value ) );
								} }
								onRemove={ () => {
									forgetControl( clientId, control.id );
									onChange( values.withoutControl( control ) );
								} }
							/>
						) ) }
					</PanelBody>
				);
			} ) }
		</div>
	);
}
