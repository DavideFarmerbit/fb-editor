/**
 * Pure logic behind the spacing control (no React): which rows a mode
 * shows, how rows read and write the four sides, and which mode fits a
 * value. Values are always stored per side; modes are presentation only.
 */
import type { BreakpointId, BreakpointList } from '../../../../Breakpoints/breakpoints';
import { BoxValue, Side, SIDES } from '../../../../Values/box';
import type { Length } from '../../../../Values/length';
import type { Responsive } from '../../../../Values/responsive';
import { SpacingMode } from './spacing-input';

export enum RowId {
	All = 'all',
	Vertical = 'vertical',
	Horizontal = 'horizontal',
	Top = 'top',
	Right = 'right',
	Bottom = 'bottom',
	Left = 'left',
}

export class SpacingRow {
	constructor(
		public readonly id: RowId,
		public readonly sides: readonly Side[]
	) {}
}

export function rowsFor( mode: SpacingMode ): readonly SpacingRow[] {
	switch ( mode ) {
		case SpacingMode.Linked:
			return [ new SpacingRow( RowId.All, SIDES ) ];
		case SpacingMode.Axis:
			return [ new SpacingRow( RowId.Vertical, [ Side.Top, Side.Bottom ] ), new SpacingRow( RowId.Horizontal, [ Side.Left, Side.Right ] ) ];
		case SpacingMode.Sides:
			return [
				new SpacingRow( RowId.Top, [ Side.Top ] ),
				new SpacingRow( RowId.Right, [ Side.Right ] ),
				new SpacingRow( RowId.Bottom, [ Side.Bottom ] ),
				new SpacingRow( RowId.Left, [ Side.Left ] ),
			];
	}
}

export function sameLength( a: Length | null, b: Length | null ): boolean {
	return JSON.stringify( a ) === JSON.stringify( b );
}

/** What a row reads from a box: its shared length, null if unset, or mixed. */
export type RowValue = Length | null | 'mixed';

export function rowValue( box: BoxValue | null, sides: readonly Side[] ): RowValue {
	const lengths = sides.map( ( side ) => box?.get( side ) ?? null );
	const first = lengths[ 0 ] ?? null;
	return lengths.every( ( length ) => sameLength( length, first ) ) ? first : 'mixed';
}

/** A copy of `box` with the row's sides set (or cleared); null when empty. */
export function withRow( box: BoxValue | null, sides: readonly Side[], length: Length | null ): BoxValue | null {
	const side = ( name: Side ): Length | null => ( sides.includes( name ) ? length : box?.get( name ) ?? null );
	const next = new BoxValue( side( Side.Top ), side( Side.Right ), side( Side.Bottom ), side( Side.Left ) );
	return next.isEmpty() ? null : next;
}

/**
 * The simplest allowed mode that shows `box` without "mixed" rows; the most
 * detailed allowed mode when none can.
 */
export function inferMode( box: BoxValue | null, modes: readonly SpacingMode[] ): SpacingMode {
	const fits = ( mode: SpacingMode ): boolean => rowsFor( mode ).every( ( row ) => rowValue( box, row.sides ) !== 'mixed' );
	const ordered = [ SpacingMode.Linked, SpacingMode.Axis, SpacingMode.Sides ].filter( ( mode ) => modes.includes( mode ) );
	return ordered.find( fits ) ?? ordered[ ordered.length - 1 ] ?? SpacingMode.Sides;
}

/** The nearest breakpoint, up to the base, that sets any of the row's sides. */
export function rowSource(
	responsive: Responsive,
	sides: readonly Side[],
	breakpoint: BreakpointId,
	breakpoints: BreakpointList
): BreakpointId | null {
	let source: BreakpointId | null = null;

	for ( const candidate of breakpoints ) {
		const own = responsive.at( candidate.id );
		if ( own instanceof BoxValue && sides.some( ( side ) => own.get( side ) !== null ) ) {
			source = candidate.id;
		}
		if ( candidate.id === breakpoint ) {
			return source;
		}
	}

	return null;
}

export function asBox( value: unknown ): BoxValue | null {
	return value instanceof BoxValue ? value : null;
}
