/**
 * Mirrors Fb\Breakpoints\* (PHP). Everything derived (media query, covered
 * range) arrives computed from PHP. Built only through the settings parser.
 */
import type { JsonValue } from '../Json/json';
import { InvalidJson } from '../Json/json';

declare const breakpointIdBrand: unique symbol;

/** Stable key responsive values are stored under. Never a pixel width. */
export type BreakpointId = string & { readonly [ breakpointIdBrand ]: true };

export class Breakpoint {
	constructor(
		public readonly id: BreakpointId,
		/** Short label shown on the switcher, e.g. "LG". */
		public readonly label: string,
		public readonly maxWidth: number | null,
		/** Smallest width this breakpoint covers; null for the smallest one. */
		public readonly minWidth: number | null,
		/** Fixed editor canvas width for this breakpoint, computed by PHP. */
		public readonly previewWidth: number,
		/** Computed by Fb\Breakpoints\Breakpoint::mediaQuery(); null for the base. */
		public readonly mediaQuery: string | null
	) {}

	isBase(): boolean {
		return this.maxWidth === null;
	}

}

/**
 * Desktop-first ordered list: base first, then by maxWidth descending.
 * Order and invariants are guaranteed by the PHP side.
 */
export class BreakpointList implements Iterable< Breakpoint > {
	private readonly items: readonly Breakpoint[];

	constructor(
		public readonly base: Breakpoint,
		others: readonly Breakpoint[]
	) {
		this.items = [ base, ...others ];
	}

	get( id: BreakpointId ): Breakpoint | undefined {
		return this.items.find( ( breakpoint ) => breakpoint.id === id );
	}

	get size(): number {
		return this.items.length;
	}

	map< T >( callback: ( breakpoint: Breakpoint, index: number ) => T ): T[] {
		return this.items.map( callback );
	}

	filter( predicate: ( breakpoint: Breakpoint ) => boolean ): Breakpoint[] {
		return this.items.filter( predicate );
	}

	[ Symbol.iterator ](): Iterator< Breakpoint > {
		return this.items[ Symbol.iterator ]();
	}
}

/** Boundary for the JSON of Fb\Breakpoints\BreakpointList. */
export function breakpointListFromJson( json: JsonValue ): BreakpointList {
	const width = ( key: string, item: JsonValue ): number | null => item.get( key ).nullable( ( value ) => value.int() );

	const [ base, ...others ] = json.list().map(
		( item ) =>
			new Breakpoint(
				item.get( 'id' ).string() as BreakpointId,
				item.get( 'label' ).string(),
				width( 'maxWidth', item ),
				width( 'minWidth', item ),
				item.get( 'previewWidth' ).int(),
				item.get( 'mediaQuery' ).nullable( ( query ) => query.string() )
			)
	);

	if ( base === undefined || ! base.isBase() ) {
		throw new InvalidJson( `${ json.path }: first entry must be the base` );
	}

	return new BreakpointList( base, others );
}
