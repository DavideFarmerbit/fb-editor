import { describe, expect, test } from 'vitest';

import { SpacingMode } from '../src/Blocks/Definition/Input/inputs';
import { inferMode, rowsFor, rowValue, withRow } from '../src/Blocks/Definition/Input/spacing';
import { BoxValue, Side, SIDES } from '../src/Values/box';
import { AutoLength, CustomLength, PresetLength, Unit } from '../src/Values/length';
import { PresetKind } from '../src/Presets/presets';

const px = ( n: number ) => new CustomLength( n, Unit.Px );
const ALL = [ SpacingMode.Linked, SpacingMode.Axis, SpacingMode.Sides ];

describe( 'rows', () => {
	test( 'cover every side exactly once in each mode', () => {
		for ( const mode of ALL ) {
			expect( rowsFor( mode ).flatMap( ( row ) => row.sides ).sort() ).toEqual( [ ...SIDES ].sort() );
		}
	} );

	test( 'read a shared value, null, or mixed', () => {
		const box = new BoxValue( px( 8 ), px( 4 ), px( 8 ), null );
		expect( rowValue( box, [ Side.Top, Side.Bottom ] ) ).toEqual( px( 8 ) );
		expect( rowValue( box, [ Side.Left ] ) ).toBeNull();
		expect( rowValue( box, [ Side.Right, Side.Left ] ) ).toBe( 'mixed' );
		expect( rowValue( null, SIDES ) ).toBeNull();
	} );

	test( 'write only their sides, and clear to null when nothing is left', () => {
		const json = ( value: unknown ): unknown => JSON.parse( JSON.stringify( value ) ) as unknown;
		const box = withRow( null, [ Side.Left, Side.Right ], new AutoLength() );
		expect( json( box ) ).toEqual( { right: { kind: 'auto' }, left: { kind: 'auto' } } );
		expect( json( withRow( box, [ Side.Top ], px( 1 ) ) ) ).toEqual( {
			top: { kind: 'custom', value: 1, unit: 'px' },
			right: { kind: 'auto' },
			left: { kind: 'auto' },
		} );
		expect( withRow( box, [ Side.Left, Side.Right ], null ) ).toBeNull();
	} );
} );

describe( 'inferMode', () => {
	const preset = new PresetLength( PresetKind.Spacing, '40' );

	test.each( [
		[ 'empty', null, SpacingMode.Linked ],
		[ 'all equal', BoxValue.all( preset ), SpacingMode.Linked ],
		[ 'pairs', new BoxValue( px( 8 ), px( 4 ), px( 8 ), px( 4 ) ), SpacingMode.Axis ],
		[ 'all different', new BoxValue( px( 1 ), px( 2 ), px( 3 ), px( 4 ) ), SpacingMode.Sides ],
	] )( '%s', ( _name, box, expected ) => {
		expect( inferMode( box, ALL ) ).toBe( expected );
	} );

	test( 'stays within the allowed modes', () => {
		expect( inferMode( new BoxValue( px( 1 ), px( 2 ), px( 3 ), px( 4 ) ), [ SpacingMode.Linked, SpacingMode.Axis ] ) ).toBe( SpacingMode.Axis );
	} );
} );
