/**
 * Pure logic behind the border control (no React), like spacing.ts: what a
 * row reads from and writes to the four sides, and which mode fits.
 */
import type { BreakpointId, BreakpointList } from '../../../../Breakpoints/breakpoints';
import { BorderBox, BorderSide } from '../../../../Values/border';
import type { Responsive } from '../../../../Values/responsive';
import { Side } from '../../../../Values/box';
import { rowsFor } from '../Spacing/spacing';
import { SpacingMode } from '../Spacing/spacing-input';

export type BorderRowValue = BorderSide | null | 'mixed';

function same( a: BorderSide | null, b: BorderSide | null ): boolean {
	return JSON.stringify( a ) === JSON.stringify( b );
}

export function borderRowValue( box: BorderBox | null, sides: readonly Side[] ): BorderRowValue {
	const values = sides.map( ( side ) => box?.get( side ) ?? null );
	const first = values[ 0 ] ?? null;
	return values.every( ( value ) => same( value, first ) ) ? first : 'mixed';
}

/** A copy of `box` with the row's sides set (or cleared); null when empty. */
export function withBorderRow( box: BorderBox | null, sides: readonly Side[], value: BorderSide | null ): BorderBox | null {
	const empty = value === null || value.isEmpty() ? null : value;
	const side = ( name: Side ): BorderSide | null => ( sides.includes( name ) ? empty : box?.get( name ) ?? null );
	const next = new BorderBox( side( Side.Top ), side( Side.Right ), side( Side.Bottom ), side( Side.Left ) );
	return next.isEmpty() ? null : next;
}

/** The simplest allowed mode that shows `box` without "mixed" rows. */
export function inferBorderMode( box: BorderBox | null, modes: readonly SpacingMode[] ): SpacingMode {
	const fits = ( mode: SpacingMode ): boolean => rowsFor( mode ).every( ( row ) => borderRowValue( box, row.sides ) !== 'mixed' );
	const ordered = [ SpacingMode.Linked, SpacingMode.Axis, SpacingMode.Sides ].filter( ( mode ) => modes.includes( mode ) );
	return ordered.find( fits ) ?? ordered[ ordered.length - 1 ] ?? SpacingMode.Sides;
}

/** The nearest breakpoint, up to the base, that sets any of the row's sides. */
export function borderRowSource( responsive: Responsive, sides: readonly Side[], breakpoint: BreakpointId, breakpoints: BreakpointList ): BreakpointId | null {
	let source: BreakpointId | null = null;
	for ( const candidate of breakpoints ) {
		const own = asBorder( responsive.at( candidate.id ) );
		if ( own !== null && sides.some( ( side ) => own.get( side ) !== null ) ) {
			source = candidate.id;
		}
		if ( candidate.id === breakpoint ) {
			return source;
		}
	}
	return null;
}

export function asBorder( value: unknown ): BorderBox | null {
	return value instanceof BorderBox ? value : null;
}
