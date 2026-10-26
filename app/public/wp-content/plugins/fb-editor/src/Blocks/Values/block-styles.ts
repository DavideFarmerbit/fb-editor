/**
 * Mirrors Fb\Blocks\Values\BlockStyles: definition + values → bindings.
 */
import type { BreakpointId, BreakpointList } from '../../Breakpoints/breakpoints';
import type { BlockDefinition } from '../Definition/block-definition';
import type { Selector } from '../../Styles/css';
import { StyleBinding, Visibility } from '../../Styles/compiler';
import { BlockBindings } from './block-bindings';
import type { BlockValues } from './block-values';
import type { BlockState } from '../States/BlockState.generated';
import type { StatefulValues } from './stateful-values';
import type { ValueChain } from './value-chain';

export class BlockStyles {
	constructor(
		private readonly breakpoints: BreakpointList,
		/** In the editor, core blocks' elements keep core's classes. */
		private readonly editor = false
	) {}

	/**
	 * Mirrors BlockStyles::forSource(): every look of one source. `weaker`:
	 * sources below it (a block's type defaults), for conditions.
	 */
	forSource( definition: BlockDefinition, values: StatefulValues, root: Selector, ...weaker: StatefulValues[] ): BlockBindings {
		const states = new Map< BlockState, readonly StyleBinding[] >();
		for ( const state of definition.states ) {
			const bindings = this.bindings( definition, values.state( state.id ), root.withPseudoClass( this.editor ? state.editorPseudoClass : state.pseudoClass ), values.chain( state.id, ...weaker ) );
			if ( bindings.length > 0 ) {
				states.set( state.id, bindings );
			}
		}
		return new BlockBindings( this.bindings( definition, values.base, root, values.chain( null, ...weaker ) ), states );
	}

	/** `context`: what conditions read (starting with `values`). */
	bindings( definition: BlockDefinition, values: BlockValues, root: Selector, context: ValueChain ): StyleBinding[] {
		const bindings: StyleBinding[] = [];

		for ( const control of definition.controls ) {
			const value = values.get( control.id );
			if ( control.output === null || value.isEmpty() ) {
				continue;
			}

			const hidden: BreakpointId[] = [];
			if ( control.visibleWhen !== null ) {
				for ( const breakpoint of this.breakpoints ) {
					if ( ! control.visibleWhen.evaluate( context.resolvedAt( breakpoint.id ) ) ) {
						hidden.push( breakpoint.id );
					}
				}
			}

			bindings.push(
				new StyleBinding(
					control.element === null
						? root
						: root.child( this.editor ? definition.editorElementClass( control.element ) : definition.elementClass( control.element ) ),
					control.output,
					value,
					Visibility.hiddenAt( hidden )
				)
			);
		}

		return bindings;
	}
}
