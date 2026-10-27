/**
 * Editor half of Fb\Blocks\Hiding\Hiding: the root classes of a block with
 * "Hide on". Same `fb-hide-<bp>` classes as the frontend, so the canvas
 * hides it exactly as the page does; while the editor shows hidden blocks
 * (top-bar toggle), a block hidden at the previewed breakpoint gets the
 * faded class instead, so it can still be selected.
 */
import { useSelect } from '@wordpress/data';

import { store } from '../../Editor/store';
import { settings } from '../../Editor/settings';
import { BreakpointSet } from '../../Values/breakpoint-set';
import type { BlockDefinition } from '../Definition/block-definition';
import type { StatefulValues } from '../Values/stateful-values';
import { CLASS_PREFIX, CONTROL, FADED_CLASS } from './Hiding.generated';

/** The block's own pick, else its variant's, else the type default's. */
function hideOn( definition: BlockDefinition, sources: readonly StatefulValues[] ): BreakpointSet | null {
	const id = definition.controlId( CONTROL );
	for ( const source of sources ) {
		const value = source.base.get( id ).at( source.base.baseBreakpoint() );
		if ( value !== null ) {
			return value instanceof BreakpointSet ? value : null;
		}
	}
	return null;
}

/**
 * @param sources The block's own values, then what they fall back to,
 *                strongest first.
 */
export function useHidingClasses( definition: BlockDefinition, sources: readonly StatefulValues[] ): string[] {
	const { showHidden, previewed } = useSelect(
		( select ) => ( { showHidden: select( store ).isShowingHiddenBlocks(), previewed: select( store ).getBreakpoint().id } ),
		[]
	);
	const hidden = hideOn( definition, sources );
	if ( hidden === null ) {
		return [];
	}
	if ( showHidden ) {
		return hidden.has( previewed ) ? [ FADED_CLASS ] : [];
	}
	return settings.breakpoints.filter( ( breakpoint ) => hidden.has( breakpoint.id ) ).map( ( breakpoint ) => CLASS_PREFIX + breakpoint.id );
}
