import { describe, expect, test } from 'vitest';

import { parseTypedLength } from '../src/Blocks/Definition/Input/Length/typed-length';

const UNITS = [ 'px', 'rem', 'em', '%', 'vw', 'vh' ];

describe( 'typing a unit selects it', () => {
	test.each( [
		[ '10%', '10', '%' ],
		[ '2rem', '2', 'rem' ],
		[ '1.5 em', '1.5', 'em' ],
		[ '-12PX', '-12', 'px' ],
		[ '100vw', '100', 'vw' ],
		[ 'rem', '', 'rem' ],
	] )( '%p → %p + %p', ( typed, text, unit ) => {
		const parsed = parseTypedLength( typed, UNITS, false );
		expect( [ parsed.text, parsed.unit, parsed.auto ] ).toEqual( [ text, unit, false ] );
	} );

	test.each( [ [ '10' ], [ '10r' ], [ '10re' ], [ '-' ], [ '1.' ], [ '10pt' ] ] )( '%p stays as typed', ( typed ) => {
		const parsed = parseTypedLength( typed, UNITS, false );
		expect( [ parsed.text, parsed.unit ] ).toEqual( [ typed, null ] );
	} );

	test( '"auto" only where allowed', () => {
		expect( parseTypedLength( 'Auto', UNITS, true ).auto ).toBe( true );
		expect( parseTypedLength( 'auto', UNITS, false ).auto ).toBe( false );
	} );
} );
