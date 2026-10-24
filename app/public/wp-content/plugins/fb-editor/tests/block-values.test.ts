/**
 * Editing values in the editor: what the sidebar writes must be the
 * canonical form PHP produces, and inheritance info must be right.
 */
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';

import { describe, expect, test } from 'vitest';

import { blockDefinitionFromJson } from '../src/Blocks/Definition/block-definition';
import type { ControlId } from '../src/Blocks/Definition/control-id';
import { BlockValues } from '../src/Blocks/Values/block-values';
import { breakpointListFromJson, type BreakpointId } from '../src/Breakpoints/breakpoints';
import { JsonValue } from '../src/Json/json';
import { KeywordValue } from '../src/Values/keyword';

const fixtures = JsonValue.of(
	JSON.parse( readFileSync( fileURLToPath( new URL( './Fixtures/styles.json', import.meta.url ) ), 'utf8' ) ) as unknown,
	'styles.json'
);
const breakpoints = breakpointListFromJson( fixtures.get( 'breakpoints' ) );

/** The real fb/section definition, as PHP exports it. */
const section = blockDefinitionFromJson(
	fixtures
		.get( 'cases' )
		.list()
		.map( ( json ) => json.get( 'definition' ) )
		.find( ( json ) => json.get( 'name' ).string() === 'fb/section' ) ??
		( () => {
			throw new Error( 'no fb/section case in fixtures' );
		} )()
);

const control = ( id: string ) => {
	const found = section.control( id as ControlId );
	if ( found === undefined ) {
		throw new Error( `no control ${ id }` );
	}
	return found;
};
const bp = ( id: string ) => id as BreakpointId;

describe( 'BlockValues.with', () => {
	test( 'writes per breakpoint, in definition order', () => {
		const values = BlockValues.fromAttribute( {}, section, breakpoints )
			.with( control( 'display' ), bp( 'mobile' ), new KeywordValue( 'block' ) )
			.with( control( 'tag' ), bp( 'mobile' ), new KeywordValue( 'header' ) )
			.with( control( 'display' ), bp( 'desktop' ), new KeywordValue( 'flex' ) );

		// Static "tag" lands on the base, before "display", whatever the edit order.
		expect( values.toAttribute() ).toEqual( { tag: 'header', display: { mobile: 'block', desktop: 'flex' } } );
	} );

	test( 'clearing the last value removes the control', () => {
		const values = BlockValues.fromAttribute( { display: { tablet: 'grid' } }, section, breakpoints ).with(
			control( 'display' ),
			bp( 'tablet' ),
			null
		);

		expect( values.toAttribute() ).toEqual( {} );
	} );

	test( 'round-trips through the attribute unchanged', () => {
		const attribute = { tag: 'div', display: { desktop: 'flex', mobile: 'block' } };
		expect( BlockValues.fromAttribute( attribute, section, breakpoints ).toAttribute() ).toEqual( attribute );
	} );
} );

describe( 'Responsive.source', () => {
	const display = BlockValues.fromAttribute( { display: { desktop: 'flex', mobile: 'block' } }, section, breakpoints ).get(
		control( 'display' ).id
	);

	test.each( [
		[ 'desktop', 'desktop' ],
		[ 'tablet', 'desktop' ],
		[ 'mobile', 'mobile' ],
	] )( '%s takes its value from %s', ( at, from ) => {
		expect( display.source( bp( at ), breakpoints ) ).toBe( from );
	} );

	test( 'nothing set anywhere has no source', () => {
		expect(
			BlockValues.fromAttribute( {}, section, breakpoints ).get( control( 'display' ).id ).source( bp( 'mobile' ), breakpoints )
		).toBeNull();
	} );
} );
