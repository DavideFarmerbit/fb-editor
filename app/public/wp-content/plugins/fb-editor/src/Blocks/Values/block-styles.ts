/**
 * Mirrors Fb\Blocks\Values\BlockStyles: definition + values → bindings.
 */
import type { BreakpointId, BreakpointList } from '../../Breakpoints/breakpoints';
import type { BlockDefinition } from '../Definition/block-definition';
import type { Selector } from '../../Styles/css';
import { StyleBinding, Visibility } from '../../Styles/compiler';
import { BlockBindings } from './block-bindings';
import type { BlockValues } from './block-values';
import { definitionOf, type StatefulValues } from './stateful-values';
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
	 *
	 * In the editor, a look's media conditions don't read the editing
	 * device: the look applies inside the canvas page's preview classes
	 * (View menu) instead of a media query.
	 */
	forSource( definition: BlockDefinition, values: StatefulValues, root: Selector, ...weaker: StatefulValues[] ): BlockBindings {
		let bindings = new BlockBindings();
		for ( const look of values.looks() ) {
			const states = look.key.hasStates() ? root.withPseudoClass( look.key.pseudoClass( definition, this.editor ) ) : root;
			const part = look.key.part === null ? undefined : definition.part( look.key.part );
			const target = part === undefined ? states : part.selector( states, definition, this.editor );
			bindings = bindings.with(
				look.key,
				this.editor ? null : look.key.mediaQuery( definition ),
				this.bindings(
					definitionOf( look.key, definition ),
					look.values,
					this.editor ? target.inside( look.key.previewClasses( definition ) ) : target,
					values.chain( look.key, ...weaker )
				)
			);
		}
		return bindings;
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
