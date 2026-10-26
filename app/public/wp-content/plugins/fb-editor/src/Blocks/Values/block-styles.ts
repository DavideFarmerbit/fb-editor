/**
 * Mirrors Fb\Blocks\Values\BlockStyles: definition + values → bindings.
 */
import type { BreakpointId, BreakpointList } from '../../Breakpoints/breakpoints';
import type { BlockDefinition } from '../Definition/block-definition';
import type { Selector } from '../../Styles/css';
import { StyleBinding, Visibility } from '../../Styles/compiler';
import type { BlockValues } from './block-values';

export class BlockStyles {
	constructor( private readonly breakpoints: BreakpointList ) {}

	/**
	 * `defaults`: the type's defaults, for conditions (an instance control's
	 * condition reads the instance's value, else the default).
	 */
	bindings(
		definition: BlockDefinition,
		values: BlockValues,
		root: Selector,
		defaults: BlockValues | null = null,
		/** In the editor, core blocks' elements keep core's classes. */
		editor = false
	): StyleBinding[] {
		const bindings: StyleBinding[] = [];

		for ( const control of definition.controls ) {
			const value = values.get( control.id );
			if ( control.output === null || value.isEmpty() ) {
				continue;
			}

			const hidden: BreakpointId[] = [];
			if ( control.visibleWhen !== null ) {
				for ( const breakpoint of this.breakpoints ) {
					if ( ! control.visibleWhen.evaluate( values.resolvedAt( breakpoint.id, defaults ) ) ) {
						hidden.push( breakpoint.id );
					}
				}
			}

			bindings.push(
				new StyleBinding(
					control.element === null
						? root
						: root.child( editor ? definition.editorElementClass( control.element ) : definition.elementClass( control.element ) ),
					control.output,
					value,
					Visibility.hiddenAt( hidden )
				)
			);
		}

		return bindings;
	}
}
