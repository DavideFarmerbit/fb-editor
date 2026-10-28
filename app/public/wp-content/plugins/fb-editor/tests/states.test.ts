/**
 * States in the editor: editing one look leaves the others alone, unset
 * values read through in cascade order, and the canvas preview selector.
 */
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';

import { describe, expect, test } from 'vitest';

import { looks } from './looks';
import { blockDefinitionFromJson } from '../src/Blocks/Definition/block-definition';
import type { ControlId } from '../src/Blocks/Definition/control-id';
import { fallbacksFor, WeakerSource } from '../src/Blocks/Inspector/fallbacks';
import { LookKey, MediaCondition } from '../src/Blocks/Looks/look-key';
import { BlockState } from '../src/Blocks/States/BlockState.generated';
import { BlockStyles } from '../src/Blocks/Values/block-styles';
import { BlockValues } from '../src/Blocks/Values/block-values';
import { StatefulValues } from '../src/Blocks/Values/stateful-values';
import { breakpointListFromJson, type BreakpointId } from '../src/Breakpoints/breakpoints';
import { JsonValue } from '../src/Json/json';
import { Preset, Presets } from '../src/Presets/presets';
import { StyleCompiler } from '../src/Styles/compiler';
import { CssLayer, Selector } from '../src/Styles/css';

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
const weight = ( value: string ) => ( { fontWeight: { desktop: value } } );
const stateful = ( attribute: unknown ) => StatefulValues.fromJson( attribute, button, breakpoints );
const look = ( attribute: unknown ) => BlockValues.fromAttribute( attribute, button, breakpoints );
const id = ( value: string ) => value as ControlId;
const desktop = 'desktop' as BreakpointId;

describe( 'editing one look', () => {
	test( 'replaces only that state', () => {
		const values = stateful( looks( weight( '400' ), { hover: weight( '700' ) } ) );

		expect( json( values.withLook( LookKey.ofState( BlockState.Active ), look( weight( '800' ) ) ) ) ).toEqual( looks( weight( '400' ), { hover: weight( '700' ), active: weight( '800' ) } ) );
	} );

	test( 'an emptied state disappears', () => {
		const values = stateful( looks( weight( '400' ), { hover: weight( '700' ) } ) );

		expect( json( values.withLook( LookKey.ofState( BlockState.Hover ), look( {} ) ) ) ).toEqual( looks( weight( '400' ) ) );
	} );
} );

describe( 'fallbacks of a state', () => {
	const defaults = new WeakerSource( stateful( looks( weight( '300' ), { hover: weight( '600' ) } ) ), 'From Button default' );

	test( 'default state, then own base, then default base', () => {
		const fallbacks = ( values: StatefulValues ) => fallbacksFor( button, values, [ defaults ], LookKey.ofState( BlockState.Hover ) );
		const read = ( values: StatefulValues ) => {
			const found = fallbacks( values ).resolve( id( 'fontWeight' ), desktop, breakpoints );
			return found === null ? null : [ json( found.value ), found.status ];
		};

		expect( read( stateful( looks( weight( '400' ) ) ) ) ).toEqual( [ '600', 'From Button default (Hover)' ] );

		const noDefaultHover = new WeakerSource( stateful( looks( weight( '300' ) ) ), 'From Button default' );
		const found = fallbacksFor( button, stateful( looks( weight( '400' ) ) ), [ noDefaultHover ], LookKey.ofState( BlockState.Hover ) ).resolve(
			id( 'fontWeight' ),
			desktop,
			breakpoints
		);
		expect( [ json( found?.value ), found?.status ] ).toEqual( [ '400', 'From base' ] );
	} );

	test( 'the base look falls back to the default base only', () => {
		const found = fallbacksFor( button, stateful( {} ), [ defaults ], LookKey.BASE ).resolve( id( 'fontWeight' ), desktop, breakpoints );
		expect( [ json( found?.value ), found?.status ] ).toEqual( [ '300', 'From Button default' ] );
	} );
} );

test( 'a hover look in dark mode reads own hover, then weaker hover, then dark, then base', () => {
	const hoverInDark = new LookKey( [ BlockState.Hover ], [ MediaCondition.Dark ] );
	const own = stateful( {
		looks: [ { values: weight( '400' ) }, { when: [ 'dark' ], values: weight( '500' ) }, { states: [ 'hover' ], values: weight( '700' ) } ],
	} );
	const defaults = new WeakerSource( stateful( looks( weight( '300' ), { hover: weight( '600' ) } ) ), 'From Button default' );
	const statuses = fallbacksFor( button, own, [ defaults ], hoverInDark ).sources.map( ( source ) => source.status );

	expect( statuses ).toEqual( [ 'From Hover', 'From Button default (Hover)', 'From Dark mode', 'From base', 'From Button default' ] );
} );

test( 'the editor previews a state with its preview class', () => {
	const compiler = new StyleCompiler( breakpoints, new Presets( fixtures.get( 'presets' ).list().map( Preset.fromJson ) ) );
	const css = new BlockStyles( breakpoints, true )
		.forSource( button, stateful( looks( null, { hover: weight( '700' ) } ) ), Selector.forClass( 'fb-i-x' ) )
		.css( compiler, CssLayer.Instances );

	expect( css ).toBe( '@layer fb.states.instances{:where(.fb-i-x:is(:hover,.fb-preview-hover)>.wp-block-button__link){font-weight:700;}}' );
} );

test( 'in the editor, conditions follow the canvas preview classes, not the editing device', () => {
	const compiler = new StyleCompiler( breakpoints, new Presets( fixtures.get( 'presets' ).list().map( Preset.fromJson ) ) );
	const values = stateful( {
		looks: [
			{ when: [ 'dark' ], values: weight( '300' ) },
			{ states: [ 'hover' ], when: [ 'touch', 'dark' ], values: weight( '800' ) },
		],
	} );

	expect( new BlockStyles( breakpoints, true ).forSource( button, values, Selector.forClass( 'fb-i-x' ) ).css( compiler, CssLayer.Instances ) ).toBe(
		'@layer fb.instances{:where(.fb-when-dark .fb-i-x>.wp-block-button__link){font-weight:300;}}' +
			'@layer fb.states.instances{:where(.fb-when-touch.fb-when-dark .fb-i-x:is(:hover,.fb-preview-hover)>.wp-block-button__link){font-weight:800;}}'
	);
	// The page itself keeps the real media queries.
	expect( new BlockStyles( breakpoints ).forSource( button, values, Selector.forClass( 'fb-i-x' ) ).css( compiler, CssLayer.Instances ) ).toContain(
		'@media (hover: none) and (pointer: coarse) and (prefers-color-scheme: dark){'
	);
} );
