/**
 * The sidebar for everything one source styles a block with: a look bar
 * (Base · Hover · …) over the Inspector of the chosen look (see LookKey).
 *
 * Each look has its own list of controls: hover starts empty even when the
 * base has values, and only shows what's set (or added) for hover. Unset
 * values read through in cascade order, e.g. for a block's hover:
 *
 *   own hover → Button default (Hover) → own base → Button default
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { store } from '../../Editor/store';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import { LookKey } from '../Looks/look-key';
import { BlockValues } from '../Values/block-values';
import { definitionOf, type StatefulValues } from '../Values/stateful-values';
import { fallbacksFor, lookLabel, type WeakerSource } from './fallbacks';
import { Inspector } from './inspector';
import type { ParentContext } from './parent-context';
import { PartsTree } from './parts-tree';
import { StateBar, StateBarItem } from './state-bar';

interface StatefulInspectorProps {
	readonly definition: BlockDefinition;
	readonly values: StatefulValues;
	/** Strongest first. */
	readonly weaker: readonly WeakerSource[];
	/** Also list controls only the first weaker source sets (defaults editing). */
	readonly listFallbackControls: boolean;
	/** Offer controls that belong to the block's place (false for defaults). */
	readonly allowPlaced: boolean;
	/** The block's parent, for placed controls. */
	readonly parent: ParentContext | null;
	/**
	 * Offers the All · Settings · Styles filter (block sidebars) and follows
	 * it; otherwise every control is listed (type defaults). Settings have
	 * no states (none of them changes on hover): in that view the
	 * state bar stays, disabled, so nothing below it moves.
	 */
	readonly filterable: boolean;
	/** The block clientId, or a block type when editing defaults. */
	readonly memoryKey: string;
	readonly onChange: ( values: StatefulValues ) => void;
	/** Copies a control of a look to the type default; null where not offered. */
	readonly makeDefault: ( ( control: Control, look: LookKey ) => void ) | null;
}

/**
 * Parts added this session are remembered with the added controls (same
 * store, same lifetime), under the memory key plus this suffix.
 */
const PARTS_MEMORY = '#parts';

export function StatefulInspector( props: StatefulInspectorProps ): ReactElement {
	const { definition, values, weaker, listFallbackControls, allowPlaced, filterable, parent, memoryKey, onChange, makeDefault } = props;
	const inspectedId = useSelect( ( select ) => select( store ).getInspectedLook( memoryKey ), [ memoryKey ] );
	const chosenView = useSelect( ( select ) => select( store ).getSidebarView(), [] );
	const addedParts = useSelect( ( select ) => select( store ).getAddedControls( `${ memoryKey }${ PARTS_MEMORY }` ), [ memoryKey ] );
	const view = filterable ? chosenView : null;
	const { inspectLook, addControl, forgetControl } = useDispatch( store );

	// A look the block can't have (anymore) is the base; so is everything
	// in the Settings view (parts are styles: hidden there too).
	const statesApply = view !== 'settings';
	const inspected = LookKey.fromId( inspectedId );
	const valid =
		inspected.states.every( ( state ) => definition.states.some( ( info ) => info.id === state ) ) &&
		( inspected.part === null || definition.part( inspected.part ) !== undefined );
	const key = statesApply && valid ? inspected : LookKey.BASE;
	const edited = definitionOf( key, definition );
	const firstWeaker = weaker[ 0 ]?.values.look( key ) ?? null;

	const items = [ LookKey.BASE, ...definition.states.map( ( info ) => LookKey.ofState( info.id ) ) ].map( ( candidate ) => {
		const onPart = candidate.onPart( key.part );
		return new StateBarItem( onPart.id(), lookLabel( definition, onPart ), ! values.look( onPart ).isEmpty() );
	} );

	return (
		<>
			{ statesApply && definition.parts.length > 0 && (
				<PartsTree
					definition={ definition }
					values={ values }
					current={ key.part }
					added={ addedParts }
					// The state stays: hovering the block styles its parts too.
					onSelect={ ( part ) => inspectLook( memoryKey, key.onPart( part ) ) }
					onAdd={ ( part ) => {
						addControl( `${ memoryKey }${ PARTS_MEMORY }`, part.id );
						inspectLook( memoryKey, key.onPart( part.id ) );
					} }
					onRemove={ ( part ) => {
						forgetControl( `${ memoryKey }${ PARTS_MEMORY }`, part.id );
						if ( key.part === part.id ) {
							inspectLook( memoryKey, key.onPart( null ) );
						}
						onChange( values.withoutPart( part.id ) );
					} }
				/>
			) }
			{ definition.states.length > 0 && (
				<StateBar
					items={ items }
					current={ key.id() }
					disabledReason={ statesApply ? null : __( 'Settings are the same in every state', 'fb-editor' ) }
					onSelect={ ( id ) => inspectLook( memoryKey, LookKey.fromId( id ) ) }
				/>
			) }
			<Inspector
				// A fresh panel per look: open panels and edit state don't leak.
				key={ key.id() }
				definition={ edited }
				values={ values.look( key ) }
				fallbacks={ fallbacksFor( definition, values, weaker, key ) }
				fallbackLook={ firstWeaker ?? BlockValues.empty( edited, values.base.breakpoints() ) }
				listFallbackControls={ listFallbackControls }
				statefulOnly={ ! key.isBase() }
				view={ view }
				filterable={ filterable }
				allowPlaced={ allowPlaced && key.part === null }
				parent={ parent }
				memoryKey={ key.isBase() ? memoryKey : `${ memoryKey }#${ key.id() }` }
				onChange={ ( look ) => onChange( values.withLook( key, look ) ) }
				makeDefault={ makeDefault === null ? null : ( control ) => makeDefault( control, key ) }
			/>
		</>
	);
}
