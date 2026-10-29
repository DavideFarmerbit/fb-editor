/**
 * Variants in the editor read the same JSON PHP writes: the theme's
 * fb.json list (invalid entries dropped), saved ones laid over by slug, and
 * the block attribute keeping its variant next to its own values.
 */
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';

import { describe, expect, test } from 'vitest';

import { looks } from './looks';
import { ActionCatalog } from '../src/Actions/action-catalog';
import { blockDefinitionFromJson } from '../src/Blocks/Definition/block-definition';
import { BlockAttribute } from '../src/Blocks/Values/block-attribute';
import { StatefulValues } from '../src/Blocks/Values/stateful-values';
import { parseVariantSlug, VariantSet } from '../src/Blocks/Variants/variant-set';
import { breakpointListFromJson } from '../src/Breakpoints/breakpoints';
import { JsonValue } from '../src/Json/json';

const read = ( file: string ): unknown => JSON.parse( readFileSync( fileURLToPath( new URL( file, import.meta.url ) ), 'utf8' ) ) as unknown;

const fixtures = JsonValue.of( read( './Fixtures/styles.json' ), 'styles.json' );
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
const themeVariants = JsonValue.of( read( './Fixtures/fb-variants.json' ), 'fb-variants.json' ).get( 'variants' ).get( 'core/button' ).decoded();

describe( 'variant sets', () => {
	test( 'theme variants drop invalid entries, as in PHP', () => {
		expect( json( VariantSet.fromJson( themeVariants, button, breakpoints ) ) ).toEqual( [
			{
				slug: 'primary',
				name: 'Primary',
				values: looks( { fontWeight: { desktop: '600' } }, { hover: { fontWeight: { desktop: '800' } } } ),
			},
			{ slug: 'ghost', name: 'ghost', values: {} },
		] );
	} );

	test( 'saved variants overlay theme ones by slug', () => {
		const theme = VariantSet.fromJson( themeVariants, button, breakpoints );
		const saved = VariantSet.fromJson(
			[
				{ slug: 'big', name: 'Big' },
				{ slug: 'primary', name: 'Main', values: looks( { fontWeight: { mobile: '700' } } ) },
			],
			button,
			breakpoints
		);

		expect( json( theme.overlay( saved ) ) ).toEqual( [
			{
				slug: 'primary',
				name: 'Main',
				values: looks( { fontWeight: { desktop: '600', mobile: '700' } }, { hover: { fontWeight: { desktop: '800' } } } ),
			},
			{ slug: 'ghost', name: 'ghost', values: {} },
			{ slug: 'big', name: 'Big', values: {} },
		] );
	} );

	test( 'slugs never contain the class separator', () => {
		expect( parseVariantSlug( 'primary-large' ) ).toBe( 'primary-large' );
		expect( parseVariantSlug( 'primary--large' ) ).toBeNull();
		expect( parseVariantSlug( 'Primary' ) ).toBeNull();
		expect( button.variantClass( 'primary' as never ) ).toBe( 'fb-button--primary' );
	} );
} );

describe( 'block attribute', () => {
	test( 'keeps the variant next to the values', () => {
		const attribute = BlockAttribute.fromJson( { variant: 'primary', junk: 1, ...looks( { fontWeight: { desktop: '700' } } ) }, button, breakpoints, ActionCatalog.EMPTY );
		expect( json( attribute ) ).toEqual( { variant: 'primary', ...looks( { fontWeight: { desktop: '700' } } ) } );
		expect( json( attribute.withVariant( null ) ) ).toEqual( looks( { fontWeight: { desktop: '700' } } ) );
		expect(
			json( attribute.withValues( StatefulValues.fromJson( looks( null, { hover: { fontWeight: { desktop: '800' } } } ), button, breakpoints ) ) )
		).toEqual( { variant: 'primary', ...looks( null, { hover: { fontWeight: { desktop: '800' } } } ) } );
	} );

	test( 'an invalid variant is dropped', () => {
		expect( BlockAttribute.fromJson( { variant: 'Not ok' }, button, breakpoints, ActionCatalog.EMPTY ).variant ).toBeNull();
	} );
} );
