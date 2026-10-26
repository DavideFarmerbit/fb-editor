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
import { StatefulValues } from '../src/Blocks/Values/stateful-values';
import { ValueChain } from '../src/Blocks/Values/value-chain';
import { breakpointListFromJson, type BreakpointId } from '../src/Breakpoints/breakpoints';
import { JsonValue } from '../src/Json/json';
import { KeywordValue } from '../src/Values/keyword';

const fixtures = JsonValue.of(
	JSON.parse( readFileSync( fileURLToPath( new URL( './Fixtures/styles.json', import.meta.url ) ), 'utf8' ) ) as unknown,
	'styles.json'
);
const breakpoints = breakpointListFromJson( fixtures.get( 'breakpoints' ) );

/** A real definition, as PHP exports it (the first case using it). */
const definition = ( name: string ) =>
	blockDefinitionFromJson(
		fixtures
			.get( 'cases' )
			.list()
			.map( ( json ) => json.get( 'definition' ) )
			.find( ( json ) => json.get( 'name' ).string() === name ) ??
			( () => {
				throw new Error( `no ${ name } case in fixtures` );
			} )()
	);

const section = definition( 'fb/section' );

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

describe( 'condition hints', () => {
	test( 'describe themselves with the definition labels', () => {
		expect( control( 'gap' ).visibleWhen?.describe( section ) ).toBe( 'Display: Flex or Grid' );
	} );
} );

describe( 'defaults in the editor', () => {
	const json = ( value: unknown ): unknown => JSON.parse( JSON.stringify( value ) ) as unknown;
	const values = ( attribute: unknown ) => BlockValues.fromAttribute( attribute, section, breakpoints );

	test( 'saved defaults overlay theme defaults per breakpoint', () => {
		const theme = values( { display: { desktop: 'flex', mobile: 'block' } } );
		const saved = values( { display: { mobile: 'grid' } } );
		expect( json( theme.overlay( saved ) ) ).toEqual( { display: { desktop: 'flex', mobile: 'grid' } } );
	} );

	test( '"Make default" copies one control, every breakpoint, leaving the rest', () => {
		const block = values( { display: { desktop: 'grid', tablet: 'flex' }, tag: 'aside' } );
		const saved = values( { tag: 'header' } );
		expect( json( saved.withControlFrom( control( 'display' ), block ) ) ).toEqual( {
			tag: 'header',
			display: { desktop: 'grid', tablet: 'flex' },
		} );
	} );

	test( 'conditions read the block value, else the default', () => {
		const defaults = values( { display: { desktop: 'flex', mobile: 'block' } } );
		const gap = control( 'gap' ).visibleWhen;

		const chain = ( own: BlockValues ) => new ValueChain( section, own, defaults );

		expect( gap?.evaluate( chain( values( {} ) ).resolvedAt( bp( 'desktop' ) ) ) ).toBe( true );
		expect( gap?.evaluate( chain( values( {} ) ).resolvedAt( bp( 'mobile' ) ) ) ).toBe( false );
		expect( gap?.evaluate( chain( values( { display: { mobile: 'grid' } } ) ).resolvedAt( bp( 'mobile' ) ) ) ).toBe( true );
	} );

	test( 'the tag falls back to the default, then the input fallback', () => {
		const base = ( attribute: unknown ) => StatefulValues.fromJson( { base: attribute }, section, breakpoints );

		expect( section.option( 'tag', base( {} ), null, base( { tag: 'aside' } ) ) ).toBe( 'aside' );
		expect( section.option( 'tag', base( {} ), base( { tag: 'header' } ), base( { tag: 'aside' } ) ) ).toBe( 'header' );
		expect( section.option( 'tag', base( { tag: 'main' } ), base( { tag: 'header' } ), base( { tag: 'aside' } ) ) ).toBe( 'main' );
		expect( section.option( 'tag', base( {} ), null, base( {} ) ) ).toBe( 'section' );
	} );

	test( 'editing the base look keeps the states', () => {
		const button = definition( 'core/button' );
		const weight = ( value: string ) => ( { fontWeight: { desktop: value } } );
		const current = StatefulValues.fromJson( { base: weight( '400' ), states: { hover: weight( '700' ) } }, button, breakpoints );

		expect( json( current.withBase( BlockValues.fromAttribute( weight( '500' ), button, breakpoints ) ) ) ).toEqual( {
			base: weight( '500' ),
			states: { hover: weight( '700' ) },
		} );
	} );
} );
