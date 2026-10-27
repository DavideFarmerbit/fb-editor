/**
 * Mirrors Fb\Values\BreakpointSet: some breakpoints, picked one by one.
 * Each stands for its own range of widths only: nothing cascades. May be
 * empty: "none", which still overrides a weaker source's pick.
 */
import type { BreakpointId } from '../Breakpoints/breakpoints';
import type { ResponsiveValue } from './value';

export class BreakpointSet implements ResponsiveValue {
	readonly breakpoints: readonly BreakpointId[];

	constructor( breakpoints: readonly BreakpointId[] ) {
		this.breakpoints = [ ...new Set( breakpoints ) ];
	}

	has( breakpoint: BreakpointId ): boolean {
		return this.breakpoints.includes( breakpoint );
	}

	/** This set with the breakpoint added or removed. */
	toggled( breakpoint: BreakpointId ): BreakpointSet {
		return new BreakpointSet( this.has( breakpoint ) ? this.breakpoints.filter( ( id ) => id !== breakpoint ) : [ ...this.breakpoints, breakpoint ] );
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): readonly BreakpointId[] {
		return this.breakpoints;
	}
}
