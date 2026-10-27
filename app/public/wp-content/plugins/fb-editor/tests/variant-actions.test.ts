/**
 * The variant menu's actions: what the block and the saved variants become.
 */
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';

import { describe, expect, test } from 'vitest';

import { blockDefinitionFromJson } from '../src/Blocks/Definition/block-definition';
import { BlockAttribute } from '../src/Blocks/Values/block-attribute';
import { materialize, saveAsNewVariant, updateVariant } from '../src/Blocks/Variants/variant-actions';
import { slugFromName, VariantSet } from '../src/Blocks/Variants/variant-set';
import { breakpointListFromJson } from '../src/Breakpoints/breakpoints';
import { JsonValue } from '../src/Json/json';

const fixtures = JsonValue.of(
	JSON.parse( readFileSync( fileURLToPath( new URL( './Fixtures/styles.json', import.meta.url ) ), 'utf8' ) ) as unknown,
	'styles.json'
);
const breakpoints = breakpointListFromJson( fixtures.get( 'breakpoints' ) );
const button = blockDefinitionFromJson(
	fixtures
		.get( 'cases' )
		.list()
		.map( ( json ) => json.get( 'definition' ) )
		.find( ( json ) => json.get( 'name' ).string() === 'core/button' ) ??
		( () => {
			throw new Error( 'no core/button case in fixtures' );
		} )()
);

const json = ( value: unknown ): unknown => JSON.parse( JSON.stringify( value ) ) as unknown;
const weight = ( desktop: string, mobile?: string ) => ( { fontWeight: mobile === undefined ? { desktop } : { desktop, mobile } } );
const set = ( value: unknown ) => VariantSet.fromJson( value, button, breakpoints );
const attribute = ( value: unknown ) => BlockAttribute.fromJson( value, button, breakpoints );

// Theme: "primary"; saved: an override of it plus "ghost".
const theme = set( [ { slug: 'primary', name: 'Primary', values: { base: weight( '600' ) } } ] );
const saved = set( [
	{ slug: 'primary', name: 'Primary', values: { states: { hover: weight( '800' ) } } },
	{ slug: 'ghost', name: 'Ghost', values: { base: weight( '300' ) } },
] );
const all = theme.overlay( saved );
const primary = all.get( 'primary' as never );
if ( primary === null ) {
	throw new Error( 'no primary' );
}

describe( 'variant actions', () => {
	test( 'save as new: the block’s whole look becomes the variant', () => {
		const block = attribute( { variant: 'primary', base: weight( '700', '400' ) } );
		const change = saveAsNewVariant( block, primary, all, saved, ' Big & Bold ' );

		expect( json( change.attribute ) ).toEqual( { variant: 'big-bold' } );
		expect( json( change.saved?.get( 'big-bold' as never ) ) ).toEqual( {
			slug: 'big-bold',
			name: 'Big & Bold',
			values: { base: weight( '700', '400' ), states: { hover: weight( '800' ) } },
		} );
	} );

	test( 'update: own values go into the saved part of the variant', () => {
		const change = updateVariant( button, breakpoints, attribute( { variant: 'primary', base: weight( '700' ) } ), primary, saved );

		expect( json( change.attribute ) ).toEqual( { variant: 'primary' } );
		// Saved only: the theme's values stay underneath.
		expect( json( change.saved?.get( 'primary' as never ) ) ).toEqual( {
			slug: 'primary',
			name: 'Primary',
			values: { base: weight( '700' ), states: { hover: weight( '800' ) } },
		} );
		expect( json( change.saved?.get( 'ghost' as never ) ) ).toEqual( json( saved.get( 'ghost' as never ) ) );
	} );

	test( 'materialize: the variant’s values become the block’s own', () => {
		const change = materialize( attribute( { variant: 'primary', base: weight( '700' ) } ), primary );

		expect( json( change.attribute ) ).toEqual( { base: weight( '700' ), states: { hover: weight( '800' ) } } );
		expect( change.saved ).toBeNull();
	} );

	test( 'slugs from names never clash', () => {
		expect( slugFromName( 'Ghost', all ) ).toBe( 'ghost-2' );
		expect( slugFromName( '!!!', all ) ).toBe( 'variant' );
		expect( slugFromName( 'Café Crème', all ) ).toBe( 'cafe-creme' );
	} );
} );
