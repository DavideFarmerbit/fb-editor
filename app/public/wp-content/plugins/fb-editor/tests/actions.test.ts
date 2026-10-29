/**
 * PHP ↔ JS parity for actions: the editor parses a block's anchor and rules
 * exactly as PHP does (tests/Fixtures/actions.json, from `composer generate`).
 */
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';

import { describe, expect, test } from 'vitest';

import { ActionCatalog, ActionKind, type ActionType } from '../src/Actions/action-catalog';
import { ActionRule, BlockActions, Step } from '../src/Actions/block-actions';
import { JsonValue } from '../src/Json/json';
import { KeywordValue } from '../src/Values/keyword';

const fixtures = JsonValue.of(
	JSON.parse( readFileSync( fileURLToPath( new URL( './Fixtures/actions.json', import.meta.url ) ), 'utf8' ) ) as unknown,
	'actions.json'
);
const catalog = ActionCatalog.fromJson( fixtures.get( 'catalog' ) );
const json = ( value: unknown ): unknown => JSON.parse( JSON.stringify( value ) ) as unknown;

describe( 'PHP ↔ JS parity of actions', () => {
	for ( const item of fixtures.get( 'cases' ).list() ) {
		test( item.get( 'name' ).string(), () => {
			const actions = BlockActions.fromAttribute( item.get( 'attribute' ).decoded(), catalog );
			expect( actions.anchor ).toBe( item.get( 'anchor' ).decoded() );
			expect( json( actions.rules ) ).toEqual( item.get( 'rules' ).decoded() );
		} );
	}
} );

test( 'a draft rule saves what parsing would keep', () => {
	const type = ( kind: ActionKind, name: string ): ActionType => {
		const found = catalog.find( kind, name );
		if ( found === undefined ) {
			throw new Error( `No ${ name } in the catalog.` );
		}
		return found;
	};
	const click = Step.of( type( ActionKind.Trigger, 'click' ) );
	const toggle = Step.of( type( ActionKind.Effect, 'open-close' ) );
	// The class is required: without it the step isn't saved.
	const addClass = Step.of( type( ActionKind.Effect, 'class' ) );

	expect( new ActionRule( [ click ], [], [] ).savable() ).toBeNull();
	expect( new ActionRule( [ click ], [], [ addClass ] ).savable() ).toBeNull();

	const draft = new ActionRule( [ click ], [], [ toggle, addClass ] );
	const saved = draft.savable();
	expect( saved?.effects ).toEqual( [ toggle ] );
	// And what's saved parses back to itself.
	expect( json( BlockActions.fromAttribute( json( { actions: [ saved ] } ), catalog ).rules ) ).toEqual( json( [ saved ] ) );

	const done = draft.withSteps( ActionKind.Effect, [ toggle, addClass.withParam( 'className',new KeywordValue( 'is-on' ) ) ] );
	expect( done.savable()?.effects ).toHaveLength( 2 );
} );

test( 'a block attribute keeps its actions next to its looks', () => {
	const actions = BlockActions.fromAttribute( { anchor: 'pnl001', actions: [ { on: [ { type: 'click' } ], do: [ { type: 'open-close' } ] } ] }, catalog );
	expect( json( actions ) ).toEqual( {
		anchor: 'pnl001',
		actions: [ { on: [ { type: 'click', params: {} } ], do: [ { type: 'open-close', params: {} } ] } ],
	} );
} );
