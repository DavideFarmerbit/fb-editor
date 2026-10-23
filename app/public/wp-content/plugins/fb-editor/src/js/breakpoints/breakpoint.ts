/**
 * Mirrors Fb\Breakpoints\* (PHP). Built only through `parseEditorSettings`.
 */

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

	map< T >( callback: ( breakpoint: Breakpoint ) => T ): T[] {
		return this.items.map( callback );
	}

	[ Symbol.iterator ](): Iterator< Breakpoint > {
		return this.items[ Symbol.iterator ]();
	}
}
