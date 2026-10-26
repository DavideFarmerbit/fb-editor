/**
 * What an edited look falls back to, strongest first, each with how the
 * sidebar labels a value coming from it. For a block's hover look:
 *
 *   Button default (Hover) → the block's base → Button default
 *
 * Same order as the CSS cascade (and ValueChain), so the sidebar shows what
 * the canvas shows.
 */
import { __, sprintf } from '@wordpress/i18n';

import type { BreakpointId, BreakpointList } from '../../Breakpoints/breakpoints';
import type { ResponsiveValue } from '../../Values/value';
import type { ControlId } from '../Definition/control-id';
import type { BlockState } from '../States/BlockState.generated';
import type { BlockValues } from '../Values/block-values';
import type { StatefulValues } from '../Values/stateful-values';

export class Fallback {
	constructor(
		public readonly values: BlockValues,
		/** e.g. "From Button default", "From base". */
		public readonly status: string
	) {}
}

export class FallbackValue {
	constructor(
		public readonly value: ResponsiveValue,
		public readonly status: string
	) {}
}

export class Fallbacks {
	constructor( public readonly sources: readonly Fallback[] ) {}

	/** The first source with a value for the control at this breakpoint. */
	resolve( id: ControlId, at: BreakpointId, breakpoints: BreakpointList ): FallbackValue | null {
		for ( const source of this.sources ) {
			const value = source.values.get( id ).resolve( at, breakpoints );
			if ( value !== null ) {
				return new FallbackValue( value, source.status );
			}
		}
		return null;
	}

	values(): BlockValues[] {
		return this.sources.map( ( source ) => source.values );
	}
}

/** A source below the edited one (type defaults, the theme's defaults). */
export class WeakerSource {
	constructor(
		public readonly values: StatefulValues,
		/** e.g. "From Section default". */
		public readonly status: string
	) {}
}

/** "From Section default (Hover)". */
function inState( status: string, stateLabel: string ): string {
	/* translators: 1: where a value comes from, e.g. "From Section default"; 2: state, e.g. "Hover" */
	return sprintf( __( '%1$s (%2$s)', 'fb-editor' ), status, stateLabel );
}

/**
 * The fallbacks of one look of `values`: in a state, the weaker sources'
 * same state, then its own base, then the weaker bases. Mirrors
 * StatefulValues.chain().
 */
export function fallbacksFor( values: StatefulValues, weaker: readonly WeakerSource[], state: BlockState | null, stateLabel: string ): Fallbacks {
	const sources: Fallback[] = [];
	if ( state !== null ) {
		sources.push( ...weaker.map( ( source ) => new Fallback( source.values.state( state ), inState( source.status, stateLabel ) ) ) );
		sources.push( new Fallback( values.base, __( 'From base', 'fb-editor' ) ) );
	}
	sources.push( ...weaker.map( ( source ) => new Fallback( source.values.base, source.status ) ) );
	return new Fallbacks( sources );
}
