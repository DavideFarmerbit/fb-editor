/**
 * The sidebar for everything one source styles a block with: a state bar
 * (Base · Hover · …) over the Inspector of the chosen look.
 *
 * Each look has its own list of controls: hover starts empty even when the
 * base has values, and only shows what's set (or added) for hover. Unset
 * values read through in cascade order, e.g. for a block's hover:
 *
 *   own hover → Button default (Hover) → own base → Button default
 */
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { store } from '../../Editor/store';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import type { BlockState } from '../States/BlockState.generated';
import { BlockValues } from '../Values/block-values';
import type { StatefulValues } from '../Values/stateful-values';
import { fallbacksFor, type WeakerSource } from './fallbacks';
import { Inspector } from './inspector';
import type { ParentContext } from './parent-context';

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
	/** The block clientId, or a block type when editing defaults. */
	readonly memoryKey: string;
	readonly onChange: ( values: StatefulValues ) => void;
	/** Copies a control of a look to the type default; null where not offered. */
	readonly makeDefault: ( ( control: Control, state: BlockState | null ) => void ) | null;
}

export function StatefulInspector( props: StatefulInspectorProps ): ReactElement {
	const { definition, values, weaker, listFallbackControls, allowPlaced, parent, memoryKey, onChange, makeDefault } = props;
	const inspected = useSelect( ( select ) => select( store ).getInspectedState( memoryKey ), [ memoryKey ] );
	const { inspectState } = useDispatch( store );

	// A state the block doesn't support (anymore) is the base.
	const info = definition.states.find( ( candidate ) => candidate.id === inspected );
	const state = info?.id ?? null;
	const firstWeaker = weaker[ 0 ]?.values.look( state ) ?? null;

	return (
		<>
			{ definition.states.length > 0 && (
				<div className="fb-states" role="group" aria-label={ __( 'State', 'fb-editor' ) }>
					{ [ null, ...definition.states ].map( ( candidate ) => {
						const id = candidate?.id ?? null;
						const classes = [ 'fb-states__item' ];
						if ( ! values.look( id ).isEmpty() ) {
							classes.push( 'is-set' );
						}
						if ( id === state ) {
							classes.push( 'is-current' );
						}
						return (
							<Button
								key={ id ?? 'base' }
								className={ classes.join( ' ' ) }
								aria-pressed={ id === state }
								onClick={ () => inspectState( memoryKey, id ) }
							>
								{ candidate?.label ?? __( 'Base', 'fb-editor' ) }
							</Button>
						);
					} ) }
				</div>
			) }
			<Inspector
				// A fresh panel per look: open panels and edit state don't leak.
				key={ state ?? 'base' }
				definition={ definition }
				values={ values.look( state ) }
				fallbacks={ fallbacksFor( values, weaker, state, info?.label ?? '' ) }
				fallbackLook={ firstWeaker ?? BlockValues.empty( definition, values.base.breakpoints() ) }
				listFallbackControls={ listFallbackControls }
				statefulOnly={ state !== null }
				allowPlaced={ allowPlaced }
				parent={ parent }
				memoryKey={ state === null ? memoryKey : `${ memoryKey }#${ state }` }
				onChange={ ( look ) => onChange( values.withLook( state, look ) ) }
				makeDefault={ makeDefault === null ? null : ( control ) => makeDefault( control, state ) }
			/>
		</>
	);
}
