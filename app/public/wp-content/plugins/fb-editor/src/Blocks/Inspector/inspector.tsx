/**
 * Settings panels generated from the PHP definition, for a block (block
 * sidebar) or a block type's defaults (Block defaults sidebar). Starts
 * empty: a control shows once it has a value somewhere, or once it's added
 * from the "Add setting" menu (kept for the session even while empty).
 * Panels show only when they hold a shown control.
 */
import { PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import type { ReactElement } from 'react';

import { store } from '../../Editor/store';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import type { BlockValues } from '../Values/block-values';
import { ValueChain } from '../Values/value-chain';
import { AddSetting } from './add-setting';
import { ControlFrame } from './control-frame';
import './inspector.scss';

interface InspectorProps {
	readonly definition: BlockDefinition;
	/** What is edited: a block's values, or a block type's saved defaults. */
	readonly values: BlockValues;
	/** What unset values fall back to (and conditions read after `values`). */
	readonly fallback: BlockValues;
	/** e.g. "From Section default" / "From theme". */
	readonly fallbackStatus: string;
	/**
	 * Keys the session memory of added controls: the block clientId, or a
	 * block type when editing defaults.
	 */
	readonly memoryKey: string;
	/** List controls that only have a fallback value too (defaults editing). */
	readonly listFallbackControls: boolean;
	readonly onChange: ( values: BlockValues ) => void;
	/** Copies a control to the type default; null where not offered. */
	readonly makeDefault: ( ( control: Control ) => void ) | null;
}

export function Inspector( props: InspectorProps ): ReactElement {
	const { definition, values, fallback, fallbackStatus, memoryKey, listFallbackControls, onChange, makeDefault } = props;
	const { breakpoint, breakpoints, added } = useSelect(
		( select ) => ( {
			breakpoint: select( store ).getBreakpoint(),
			breakpoints: select( store ).getBreakpoints(),
			added: select( store ).getAddedControls( memoryKey ),
		} ),
		[ memoryKey ]
	);
	const { addControl, forgetControl } = useDispatch( store );
	// Conditions read the edited value, else the fallback (as in PHP).
	const resolved = new ValueChain( definition, values, fallback ).resolvedAt( breakpoint.id );

	const isShown = ( control: Control ): boolean =>
		values.has( control.id ) || added.includes( control.id ) || ( listFallbackControls && fallback.has( control.id ) );
	const available = definition.controls.filter( ( control ) => ! isShown( control ) );

	return (
		<div className="fb-inspector">
			<div className="fb-inspector__toolbar">
				<AddSetting
					definition={ definition }
					available={ available }
					resolved={ resolved }
					defaults={ fallback }
					onAdd={ ( control ) => addControl( memoryKey, control.id ) }
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
								fallback={ fallback }
								fallbackStatus={ fallbackStatus }
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
									addControl( memoryKey, control.id );
									onChange( values.with( control, breakpoint.id, value ) );
								} }
								onRemove={ () => {
									forgetControl( memoryKey, control.id );
									onChange( values.withoutControl( control ) );
								} }
								onMakeDefault={ makeDefault !== null && values.has( control.id ) ? () => makeDefault( control ) : null }
							/>
						) ) }
					</PanelBody>
				);
			} ) }
		</div>
	);
}
