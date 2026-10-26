/**
 * Mirrors Fb\Blocks\Values\StatefulValues: a source's base look plus a look
 * per state, the shape of a block's `fb` attribute and of type defaults:
 *
 *   {"base": {controlId: …}, "states": {"hover": {controlId: …}}}
 */
import type { BreakpointList } from '../../Breakpoints/breakpoints';
import { JsonValue } from '../../Json/json';
import type { BlockDefinition } from '../Definition/block-definition';
import type { BlockState } from '../States/BlockState.generated';
import { BlockValues } from './block-values';
import { ValueChain } from './value-chain';

export class StatefulValues {
	private constructor(
		private readonly definition: BlockDefinition,
		public readonly base: BlockValues,
		/** Supported states only, in cascade order, none empty. */
		private readonly states: ReadonlyMap< BlockState, BlockValues >
	) {}

	static empty( definition: BlockDefinition, breakpoints: BreakpointList ): StatefulValues {
		return new StatefulValues( definition, BlockValues.empty( definition, breakpoints ), new Map() );
	}

	static fromJson( json: unknown, definition: BlockDefinition, breakpoints: BreakpointList ): StatefulValues {
		const root = JsonValue.of( json, 'fb' );
		if ( ! root.isObject() ) {
			return StatefulValues.empty( definition, breakpoints );
		}

		const base = BlockValues.fromAttribute( root.has( 'base' ) ? root.get( 'base' ).decoded() : null, definition, breakpoints );
		const statesJson = root.has( 'states' ) ? root.get( 'states' ) : null;

		const states = new Map< BlockState, BlockValues >();
		for ( const { id } of definition.states ) {
			if ( statesJson === null || ! statesJson.isObject() || ! statesJson.has( id ) ) {
				continue;
			}
			const values = BlockValues.fromAttribute( statesJson.get( id ).decoded(), definition, breakpoints ).onlyStateful();
			if ( ! values.isEmpty() ) {
				states.set( id, values );
			}
		}

		return new StatefulValues( definition, base, states );
	}

	/** One state's look; empty for states it doesn't set. */
	state( state: BlockState ): BlockValues {
		return this.states.get( state ) ?? BlockValues.empty( this.definition, this.base.breakpoints() );
	}

	isEmpty(): boolean {
		return this.base.isEmpty() && this.states.size === 0;
	}

	/** A copy with another base look (states kept). */
	withBase( base: BlockValues ): StatefulValues {
		return new StatefulValues( this.definition, base, this.states );
	}

	/** One look: a state's, or the base (null). */
	look( state: BlockState | null ): BlockValues {
		return state === null ? this.base : this.state( state );
	}

	/**
	 * A copy with one look replaced. A state keeps only what can vary per
	 * state, and disappears when empty (as when parsed).
	 */
	withLook( state: BlockState | null, values: BlockValues ): StatefulValues {
		if ( state === null ) {
			return this.withBase( values );
		}
		const states = new Map< BlockState, BlockValues >();
		for ( const { id } of this.definition.states ) {
			const next = id === state ? values.onlyStateful() : this.states.get( id );
			if ( next !== undefined && ! next.isEmpty() ) {
				states.set( id, next );
			}
		}
		return new StatefulValues( this.definition, this.base, states );
	}

	/** Mirrors StatefulValues::chain(): any state beats any base. */
	chain( state: BlockState | null, ...weaker: StatefulValues[] ): ValueChain {
		const sources: BlockValues[] = [];
		if ( state !== null ) {
			sources.push( this.state( state ), ...weaker.map( ( source ) => source.state( state ) ) );
		}
		sources.push( this.base, ...weaker.map( ( source ) => source.base ) );
		return new ValueChain( this.definition, ...sources );
	}

	/** Mirrors StatefulValues::overlay(). */
	overlay( over: StatefulValues ): StatefulValues {
		const states = new Map< BlockState, BlockValues >();
		for ( const { id } of this.definition.states ) {
			const merged = this.state( id ).overlay( over.state( id ) );
			if ( ! merged.isEmpty() ) {
				states.set( id, merged );
			}
		}
		return new StatefulValues( this.definition, this.base.overlay( over.base ), states );
	}

	/** Plain JSON for setAttributes() / post content. */
	toAttribute(): unknown {
		return JSON.parse( JSON.stringify( this ) ) as unknown;
	}

	/** Canonical form, as PHP serializes it: empty looks left out. */
	toJSON(): Record< string, unknown > {
		const json: Record< string, unknown > = {};
		if ( ! this.base.isEmpty() ) {
			json.base = this.base;
		}
		if ( this.states.size > 0 ) {
			json.states = Object.fromEntries( this.states );
		}
		return json;
	}
}
