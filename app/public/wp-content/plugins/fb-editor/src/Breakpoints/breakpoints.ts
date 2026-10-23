/**
 * Mirrors Fb\Breakpoints\* (PHP). Built only through `parseEditorSettings`.
 */

import { InvalidJson, type JsonValue } from '../Json/json';
import { BreakpointIcon } from './BreakpointIcon.generated';

declare const breakpointIdBrand: unique symbol;

/** Stable key responsive values are stored under. Never a pixel width. */
export type BreakpointId = string & { readonly [ breakpointIdBrand ]: true };

export { BreakpointIcon };

export class Breakpoint {
	constructor(
		public readonly id: BreakpointId,
		public readonly label: string,
		public readonly icon: BreakpointIcon | null,
		public readonly maxWidth: number | null,
		public readonly previewWidth: number | null,
		/** Computed by Fb\Breakpoints\Breakpoint::mediaQuery(); null for the base. */
		public readonly mediaQuery: string | null
	) {}

	isBase(): boolean {
		return this.maxWidth === null;
	}

	/** Canvas width for the editor preview; null = full width. */
	canvasWidth(): number | null {
		return this.previewWidth ?? this.maxWidth;
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

	[ Symbol.iterator ](): Iterator< Breakpoint > {
		return this.items[ Symbol.iterator ]();
	}
}

/** Boundary for the JSON of Fb\Breakpoints\BreakpointList. */
export function breakpointListFromJson( json: JsonValue ): BreakpointList {
	const [ base, ...others ] = json.list().map(
		( item ) =>
			new Breakpoint(
				item.get( 'id' ).string() as BreakpointId,
				item.get( 'label' ).string(),
				item.get( 'icon' ).nullable( ( icon ) => icon.oneOf( BreakpointIcon ) ),
				item.get( 'maxWidth' ).nullable( ( width ) => width.int() ),
				item.get( 'previewWidth' ).nullable( ( width ) => width.int() ),
				item.get( 'mediaQuery' ).nullable( ( query ) => query.string() )
			)
	);

	if ( base === undefined || ! base.isBase() ) {
		throw new InvalidJson( `${ json.path }: first entry must be the base` );
	}

	return new BreakpointList( base, others );
}
