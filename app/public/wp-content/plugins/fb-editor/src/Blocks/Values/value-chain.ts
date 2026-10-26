/**
 * Mirrors Fb\Blocks\Values\ValueChain: where a control's effective value
 * comes from, strongest source first. Each source cascades across
 * breakpoints on its own before the next one is asked.
 */
import type { BreakpointId } from '../../Breakpoints/breakpoints';
import type { ResolvedValues } from '../Definition/Condition/conditions';
import type { BlockDefinition } from '../Definition/block-definition';
import type { ResponsiveValue } from '../../Values/value';
import { ResolvedValueMap, type BlockValues } from './block-values';

export class ValueChain {
	private readonly sources: readonly BlockValues[];

	constructor(
		private readonly definition: BlockDefinition,
		...sources: BlockValues[]
	) {
		this.sources = sources;
	}

	/** Every control's effective value at a breakpoint (what conditions read). */
	resolvedAt( breakpoint: BreakpointId ): ResolvedValues {
		const resolved = new Map< string, ResponsiveValue >();
		for ( const control of this.definition.controls ) {
			for ( const source of this.sources ) {
				const at = source.get( control.id ).resolve( breakpoint, source.breakpoints() );
				if ( at !== null ) {
					resolved.set( control.id, at );
					break;
				}
			}
		}
		return new ResolvedValueMap( resolved );
	}
}
