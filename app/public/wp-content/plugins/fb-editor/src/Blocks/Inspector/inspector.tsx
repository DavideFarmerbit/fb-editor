/**
 * Settings panels generated from the PHP definition, for one look (base or
 * a state) of a block or of a block type's defaults. Starts empty but for
 * what the block needs (controls marked alwaysShown): a control shows once
 * it has a value in this look, or once it's added from the "Add setting"
 * menu (kept for the session even while empty). Panels show only when they
 * hold a shown control.
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
import type { Fallbacks } from './fallbacks';
import { describePlacement, type ParentContext } from './parent-context';
import './inspector.scss';

interface InspectorProps {
	readonly definition: BlockDefinition;
	/** What is edited: one look of a block, or of a type's saved defaults. */
	readonly values: BlockValues;
	/** What unset values fall back to (and conditions read after `values`). */
	readonly fallbacks: Fallbacks;
	/**
	 * The same look one level down (the type default for a block, the
	 * theme's for saved defaults): its controls are marked in the menu.
	 */
	readonly fallbackLook: BlockValues;
	/** Also list controls that only `fallbackLook` sets (defaults editing). */
	readonly listFallbackControls: boolean;
	/** Only controls that can vary per state (editing a state). */
	readonly statefulOnly: boolean;
	/**
	 * Controls that belong to the block's place (Container item) are
	 * offered: false for type defaults, which never hold them.
	 */
	readonly allowPlaced: boolean;
	/** The block's parent, for placed controls; null when it has none. */
	readonly parent: ParentContext | null;
	/**
	 * Keys the session memory of added controls: the block clientId, or a
	 * block type when editing defaults, plus the look.
	 */
	readonly memoryKey: string;
	readonly onChange: ( values: BlockValues ) => void;
	/** Copies a control to the type default; null where not offered. */
	readonly makeDefault: ( ( control: Control ) => void ) | null;
}

export function Inspector( props: InspectorProps ): ReactElement {
	const { definition, values, fallbacks, fallbackLook, listFallbackControls, statefulOnly, allowPlaced, parent, memoryKey, onChange, makeDefault } = props;
	const { breakpoint, breakpoints, added } = useSelect(
		( select ) => ( {
			breakpoint: select( store ).getBreakpoint(),
			breakpoints: select( store ).getBreakpoints(),
			added: select( store ).getAddedControls( memoryKey ),
		} ),
		[ memoryKey ]
	);
	const { addControl, forgetControl } = useDispatch( store );
	// Conditions read the edited value, then the fallbacks (as the CSS does).
	const resolved = new ValueChain( definition, values, ...fallbacks.values() ).resolvedAt( breakpoint.id );

	// A placed control shows where it applies, or while it still has a value
	// here (then inactive, so it can be removed).
	const appliesHere = ( control: Control ): boolean => parent?.allows( control, breakpoint.id ) ?? ! control.isPlaced();
	const controls = definition.controls.filter(
		( control ) =>
			( ! statefulOnly || control.isStateful() ) &&
			( ! control.isPlaced() || ( allowPlaced && ( appliesHere( control ) || values.has( control.id ) ) ) )
	);
	const isShown = ( control: Control ): boolean =>
		control.alwaysShown || values.has( control.id ) || added.includes( control.id ) || ( listFallbackControls && fallbackLook.has( control.id ) );
	const available = controls.filter( ( control ) => ! isShown( control ) );

	return (
		<div className="fb-inspector">
			<div className="fb-inspector__toolbar">
				<AddSetting
					definition={ definition }
					available={ available }
					resolved={ resolved }
					defaults={ fallbackLook }
					onAdd={ ( control ) => addControl( memoryKey, control.id ) }
				/>
			</div>
			{ definition.layout.map( ( panel ) => {
				const shown = panel.controls
					.map( ( id ) => controls.find( ( control ) => control.id === id ) )
					.filter( ( control ): control is Control => control !== undefined && isShown( control ) );

				if ( shown.length === 0 ) {
					return null;
				}

				return (
					<PanelBody key={ panel.label } title={ panel.label }>
						{ shown.map( ( control ) => (
							<ControlFrame
								key={ control.id }
								definition={ definition }
								control={ control }
								values={ values }
								fallbacks={ fallbacks }
								breakpoint={ breakpoint }
								breakpoints={ breakpoints }
								inactiveReason={
									! appliesHere( control )
										? describePlacement( control )
										: control.visibleWhen !== null && ! control.visibleWhen.evaluate( resolved )
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
								onMakeDefault={ makeDefault !== null && ! control.isPlaced() && values.has( control.id ) ? () => makeDefault( control ) : null }
							/>
						) ) }
					</PanelBody>
				);
			} ) }
		</div>
	);
}
