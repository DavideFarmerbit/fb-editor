/**
 * Mirrors Fb\Blocks\Values\BlockBindings: bindings split by look, the base
 * in a base layer, states in its state layer in cascade order.
 */
import { BlockState } from '../States/BlockState.generated';
import type { StyleBinding, StyleCompiler } from '../../Styles/compiler';
import { statesLayer, wrapInLayer, type CssLayer } from '../../Styles/css';

export class BlockBindings {
	constructor(
		private readonly base: readonly StyleBinding[] = [],
		private readonly states: ReadonlyMap< BlockState, readonly StyleBinding[] > = new Map()
	) {}

	merge( other: BlockBindings ): BlockBindings {
		const states = new Map( this.states );
		other.states.forEach( ( bindings, state ) => states.set( state, [ ...( states.get( state ) ?? [] ), ...bindings ] ) );
		return new BlockBindings( [ ...this.base, ...other.base ], states );
	}

	/** `layer`: the base layer (defaults or instances). */
	css( compiler: StyleCompiler, layer: CssLayer ): string {
		let states = '';
		for ( const state of Object.values( BlockState ) ) {
			states += compiler.rules( this.states.get( state ) ?? [] );
		}
		return compiler.compile( layer, this.base ) + ( states === '' ? '' : wrapInLayer( statesLayer( layer ), states ) );
	}
}
