/**
 * Mirrors Fb\Breakpoints\* (PHP). Built only through `parseEditorSettings`.
 */

import { InvalidJson, type JsonValue } from '../core/json';

declare const breakpointIdBrand: unique symbol;

/** Stable key responsive values are stored under. Never a pixel width. */
export type BreakpointId = string & { readonly [ breakpointIdBrand ]: true };

export enum BreakpointIcon {
	Desktop = 'desktop',
	Tablet = 'tablet',
	Mobile = 'mobile',
}

export class Breakpoint {
	constructor(
		public readonly id: BreakpointId,
		public readonly label: string,
		public readonly icon: BreakpointIcon | null,
		public readonly maxWidth: number | null,
		public readonly previewWidth: number | null
	) {}

	isBase(): boolean {
		return this.maxWidth === null;
	}

	/** Same string as Fb\Breakpoints\Breakpoint::mediaQuery(). */
	mediaQuery(): string | null {
		return this.maxWidth === null ? null : `(max-width: ${ this.maxWidth }px)`;
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

function parseIcon( value: string | null ): BreakpointIcon | null {
	return Object.values( BreakpointIcon ).find( ( icon ) => icon === value ) ?? null;
}

/** Boundary for the JSON of Fb\Breakpoints\BreakpointList. */
export function breakpointListFromJson( json: JsonValue ): BreakpointList {
	const [ base, ...others ] = json.list().map(
		( item ) =>
			new Breakpoint(
				item.get( 'id' ).string() as BreakpointId,
				item.get( 'label' ).string(),
				parseIcon( item.get( 'icon' ).nullable( ( icon ) => icon.string() ) ),
				item.get( 'maxWidth' ).nullable( ( width ) => width.int() ),
				item.get( 'previewWidth' ).nullable( ( width ) => width.int() )
			)
	);

	if ( base === undefined || ! base.isBase() ) {
		throw new InvalidJson( `${ json.path }: first entry must be the base` );
	}

	return new BreakpointList( base, others );
}
