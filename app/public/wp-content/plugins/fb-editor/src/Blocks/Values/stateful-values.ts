/**
 * Mirrors Fb\Blocks\Values\StatefulValues: a source's looks (see LookKey),
 * the base first. The shape of a block's `fb` attribute, of type defaults
 * and of variants:
 *
 *   {"looks": [{"values": {…}}, {"states": ["hover"], "values": {…}}]}
 */
import type { BreakpointList } from '../../Breakpoints/breakpoints';
import { InvalidJson, JsonValue } from '../../Json/json';
import type { BlockDefinition } from '../Definition/block-definition';
import type { ControlId } from '../Definition/control-id';
import { Look, LookKey } from '../Looks/look-key';
import type { PartId } from '../Parts/part';
import { BlockValues } from './block-values';
import { ValueChain } from './value-chain';

/** Mirrors StatefulValues::definitionOf(): the block's definition, or its part's. */
export function definitionOf( key: LookKey, definition: BlockDefinition ): BlockDefinition {
	return key.part === null ? definition : definition.part( key.part )?.definition ?? definition;
}

function sorted( looks: ReadonlyArray< Look< BlockValues > > ): Array< Look< BlockValues > > {
	return [ ...looks ].sort( ( a, b ) => LookKey.compare( a.key, b.key ) );
}

export class StatefulValues {
	private constructor(
		private readonly definition: BlockDefinition,
		public readonly base: BlockValues,
		/** Not the base; in cascade order, none empty, unique keys. */
		private readonly others: ReadonlyArray< Look< BlockValues > >
	) {}

	static empty( definition: BlockDefinition, breakpoints: BreakpointList ): StatefulValues {
		return new StatefulValues( definition, BlockValues.empty( definition, breakpoints ), [] );
	}

	/** Mirrors StatefulValues::fromJson(). `placed`: keep controls that belong to the block's place. */
	static fromJson( json: unknown, definition: BlockDefinition, breakpoints: BreakpointList, placed = true ): StatefulValues {
		const root = JsonValue.of( json, 'fb' );
		if ( ! root.isObject() || ! root.has( 'looks' ) ) {
			return StatefulValues.empty( definition, breakpoints );
		}

		let items: JsonValue[];
		try {
			items = root.get( 'looks' ).list();
		} catch ( error ) {
			if ( error instanceof InvalidJson ) {
				return StatefulValues.empty( definition, breakpoints );
			}
			throw error;
		}

		let base: BlockValues | null = null;
		const looks = new Map< string, Look< BlockValues > >();
		for ( const item of items ) {
			const key = LookKey.fromJson( item, definition );
			if ( key === null || ( key.isBase() ? base !== null : looks.has( key.id() ) ) ) {
				continue;
			}
			const values = BlockValues.fromAttribute( item.has( 'values' ) ? item.get( 'values' ).decoded() : null, definitionOf( key, definition ), breakpoints );
			if ( key.isBase() ) {
				base = placed ? values : values.withoutPlaced();
				continue;
			}
			const stateful = values.onlyStateful();
			if ( ! stateful.isEmpty() ) {
				looks.set( key.id(), new Look( key, stateful ) );
			}
		}

		return new StatefulValues( definition, base ?? BlockValues.empty( definition, breakpoints ), sorted( [ ...looks.values() ] ) );
	}

	/** One look's values; empty for looks it doesn't set. */
	look( key: LookKey ): BlockValues {
		if ( key.isBase() ) {
			return this.base;
		}
		return this.others.find( ( look ) => look.key.equals( key ) )?.values ?? BlockValues.empty( definitionOf( key, this.definition ), this.base.breakpoints() );
	}

	/** Every look, the base first, in cascade order. */
	looks(): Array< Look< BlockValues > > {
		return [ new Look( LookKey.BASE, this.base ), ...this.others ];
	}

	isEmpty(): boolean {
		return this.base.isEmpty() && this.others.length === 0;
	}

	/** Mirrors StatefulValues::has(): set in any look, at any breakpoint. */
	has( id: ControlId ): boolean {
		return this.looks().some( ( look ) => look.values.has( id ) );
	}

	/** Without the values that belong to the block's place: a look to share. */
	withoutPlaced(): StatefulValues {
		return new StatefulValues( this.definition, this.base.withoutPlaced(), this.others );
	}

	/** Only the values that belong to the block's place (only the base holds any). */
	onlyPlaced(): StatefulValues {
		return new StatefulValues( this.definition, this.base.onlyPlaced(), [] );
	}

	/** Without any look of a part (removing the part). */
	withoutPart( part: PartId ): StatefulValues {
		return new StatefulValues( this.definition, this.base, this.others.filter( ( look ) => look.key.part !== part ) );
	}

	/** A copy with another base look (the others kept). */
	withBase( base: BlockValues ): StatefulValues {
		return new StatefulValues( this.definition, base, this.others );
	}

	/**
	 * A copy with one look replaced. Outside the base a look keeps only what
	 * can vary per look, and disappears when empty (as when parsed).
	 */
	withLook( key: LookKey, values: BlockValues ): StatefulValues {
		if ( key.isBase() ) {
			return this.withBase( values );
		}
		const others = this.others.filter( ( look ) => ! look.key.equals( key ) );
		const stateful = values.onlyStateful();
		return new StatefulValues( this.definition, this.base, stateful.isEmpty() ? others : sorted( [ ...others, new Look( key, stateful ) ] ) );
	}

	/**
	 * Mirrors StatefulValues::chain(): every look applying within `key`, in
	 * CSS cascade order (strongest first). Any state beats any base.
	 */
	chain( key: LookKey, ...weaker: StatefulValues[] ): ValueChain {
		const sources = [ this, ...weaker ];
		const values: BlockValues[] = [];
		for ( const withStates of [ true, false ] ) {
			for ( const source of sources ) {
				for ( const look of [ ...source.looks() ].reverse() ) {
					if ( look.key.hasStates() === withStates && look.key.appliesWithin( key ) ) {
						values.push( look.values );
					}
				}
			}
		}
		return new ValueChain( definitionOf( key, this.definition ), ...values );
	}

	/** Mirrors StatefulValues::overlay(). */
	overlay( over: StatefulValues ): StatefulValues {
		const keys = new Map< string, LookKey >();
		for ( const look of [ ...this.others, ...over.others ] ) {
			keys.set( look.key.id(), look.key );
		}
		const others: Array< Look< BlockValues > > = [];
		keys.forEach( ( key ) => {
			const merged = this.look( key ).overlay( over.look( key ) );
			if ( ! merged.isEmpty() ) {
				others.push( new Look( key, merged ) );
			}
		} );
		return new StatefulValues( this.definition, this.base.overlay( over.base ), sorted( others ) );
	}

	/** Plain JSON for setAttributes() / post content. */
	toAttribute(): unknown {
		return JSON.parse( JSON.stringify( this ) ) as unknown;
	}

	/** Canonical form, as PHP serializes it: empty looks left out. */
	toJSON(): Record< string, unknown > {
		const looks = this.base.isEmpty() ? this.others : this.looks();
		return looks.length === 0 ? {} : { looks };
	}
}
