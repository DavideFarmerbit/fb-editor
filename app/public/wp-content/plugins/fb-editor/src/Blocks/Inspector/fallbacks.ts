/**
 * What an edited look falls back to, strongest first, each with how the
 * sidebar labels a value coming from it. For a block's hover look:
 *
 *   Button default (Hover) → the block's base → Button default
 *
 * (a dark-mode hover look also reads the dark-mode looks, see LookKey).
 *
 * Same order as the CSS cascade (and ValueChain), so the sidebar shows what
 * the canvas shows.
 */
import { __, sprintf } from '@wordpress/i18n';

import type { BreakpointId, BreakpointList } from '../../Breakpoints/breakpoints';
import type { ResponsiveValue } from '../../Values/value';
import type { ControlId } from '../Definition/control-id';
import type { BlockDefinition } from '../Definition/block-definition';
import type { LookKey } from '../Looks/look-key';
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

/** "Hover", "Hover + Focus · Dark mode", "Base" (of the block or of a part: the parts tree names the part). */
export function lookLabel( definition: BlockDefinition, key: LookKey ): string {
	if ( key.isPlain() ) {
		return __( 'Base', 'fb-editor' );
	}
	const states = key.states.map( ( state ) => definition.states.find( ( info ) => info.id === state )?.label ?? state );
	const conditions = key.conditions.map( ( condition ) => definition.conditions.find( ( info ) => info.id === condition )?.label ?? condition );
	return [ states.join( ' + ' ), conditions.join( ' + ' ) ].filter( ( part ) => part !== '' ).join( ' · ' );
}

/** "From Section default (Hover)". */
function inLook( status: string, label: string ): string {
	/* translators: 1: where a value comes from, e.g. "From Section default"; 2: a look, e.g. "Hover" */
	return sprintf( __( '%1$s (%2$s)', 'fb-editor' ), status, label );
}

/** "From base", "From Hover". */
function fromOwn( definition: BlockDefinition, key: LookKey ): string {
	return key.isPlain()
		? __( 'From base', 'fb-editor' )
		: /* translators: %s: another look of the same block, e.g. "Hover" */ sprintf( __( 'From %s', 'fb-editor' ), lookLabel( definition, key ) );
}

/**
 * The fallbacks of one look of `values`: every other look applying within
 * it, own and weaker, in CSS cascade order (mirrors StatefulValues.chain(),
 * without the edited look itself).
 */
export function fallbacksFor( definition: BlockDefinition, values: StatefulValues, weaker: readonly WeakerSource[], key: LookKey ): Fallbacks {
	const sources: Fallback[] = [];
	const all = [ new WeakerSource( values, '' ), ...weaker ];
	for ( const withStates of [ true, false ] ) {
		all.forEach( ( source, index ) => {
			for ( const look of [ ...source.values.looks() ].reverse() ) {
				if ( look.key.hasStates() !== withStates || ! look.key.appliesWithin( key ) || ( index === 0 && look.key.equals( key ) ) ) {
					continue;
				}
				let status: string;
				if ( index === 0 ) {
					status = fromOwn( definition, look.key );
				} else {
					status = look.key.isPlain() ? source.status : inLook( source.status, lookLabel( definition, look.key ) );
				}
				sources.push( new Fallback( look.values, status ) );
			}
		} );
	}
	return new Fallbacks( sources );
}
