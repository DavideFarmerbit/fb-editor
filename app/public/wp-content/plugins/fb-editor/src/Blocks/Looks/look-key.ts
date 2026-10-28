/**
 * Mirrors Fb\Blocks\Looks\LookKey: which look of a block, the states and
 * media conditions it requires (all at once). Selectors and queries come
 * from the definition (computed by PHP).
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import type { BlockDefinition } from '../Definition/block-definition';
import { BlockState } from '../States/BlockState.generated';
import { MediaCondition } from './MediaCondition.generated';

export { MediaCondition };

const STATE_ORDER: readonly BlockState[] = Object.values( BlockState );
const CONDITION_ORDER: readonly MediaCondition[] = Object.values( MediaCondition );

function ordered< T >( values: readonly T[], order: readonly T[] ): T[] {
	return order.filter( ( value ) => values.includes( value ) );
}

export class LookKey {
	/** In cascade order, no duplicates. */
	readonly states: readonly BlockState[];
	/** In cascade order, no duplicates. */
	readonly conditions: readonly MediaCondition[];

	constructor( states: readonly BlockState[] = [], conditions: readonly MediaCondition[] = [] ) {
		this.states = ordered( states, STATE_ORDER );
		this.conditions = ordered( conditions, CONDITION_ORDER );
	}

	static readonly BASE = new LookKey();

	static ofState( state: BlockState ): LookKey {
		return new LookKey( [ state ] );
	}

	/** From id(); the base for anything unreadable. */
	static fromId( id: string ): LookKey {
		const [ states = '', conditions = '' ] = id.split( '|' );
		const pick = < T extends string >( text: string, order: readonly T[] ): T[] =>
			text.split( '+' ).flatMap( ( value ) => order.filter( ( candidate ) => candidate === value ) );
		return new LookKey( pick( states, STATE_ORDER ), pick( conditions, CONDITION_ORDER ) );
	}

	/**
	 * Mirrors StatefulValues::parseKey(): null when the look can't apply to
	 * this block type (a state it doesn't support, an unknown condition).
	 */
	static fromJson( json: JsonValue, definition: BlockDefinition ): LookKey | null {
		if ( ! json.isObject() ) {
			return null;
		}
		try {
			const states = ( json.has( 'states' ) ? json.get( 'states' ).list() : [] ).map( ( value ) => value.string() );
			const conditions = ( json.has( 'when' ) ? json.get( 'when' ).list() : [] ).map( ( value ) => value.string() );
			if (
				! states.every( ( state ) => definition.states.some( ( info ) => info.id === state ) ) ||
				! conditions.every( ( condition ) => CONDITION_ORDER.some( ( candidate ) => candidate === condition ) )
			) {
				return null;
			}
			return new LookKey( states as BlockState[], conditions as MediaCondition[] );
		} catch ( error ) {
			if ( error instanceof InvalidJson ) {
				return null;
			}
			throw error;
		}
	}

	isBase(): boolean {
		return this.states.length === 0 && this.conditions.length === 0;
	}

	hasStates(): boolean {
		return this.states.length > 0;
	}

	/** Mirrors LookKey::appliesWithin(). */
	appliesWithin( other: LookKey ): boolean {
		return this.states.every( ( state ) => other.states.includes( state ) ) && this.conditions.every( ( condition ) => other.conditions.includes( condition ) );
	}

	equals( other: LookKey ): boolean {
		return this.id() === other.id();
	}

	/** Mirrors LookKey::id(), e.g. "hover+focus|dark"; "" for the base. */
	id(): string {
		const states = this.states.join( '+' );
		return this.conditions.length === 0 ? states : `${ states }|${ this.conditions.join( '+' ) }`;
	}

	/** Mirrors LookKey::compare(): cascade order, weakest first. */
	static compare( a: LookKey, b: LookKey ): number {
		const rank = ( key: LookKey ): number[] => [
			key.states.length,
			...key.states.map( ( state ) => STATE_ORDER.indexOf( state ) ),
			key.conditions.length,
			...key.conditions.map( ( condition ) => CONDITION_ORDER.indexOf( condition ) ),
		];
		const left = rank( a );
		const right = rank( b );
		for ( let index = 0; index < Math.max( left.length, right.length ); index++ ) {
			const difference = ( left[ index ] ?? -1 ) - ( right[ index ] ?? -1 );
			if ( difference !== 0 ) {
				return difference;
			}
		}
		return 0;
	}

	/** Mirrors LookKey::pseudoClass(); `editor`: states also match their preview class. */
	pseudoClass( definition: BlockDefinition, editor: boolean ): string {
		return this.states
			.map( ( state ) => {
				const info = definition.states.find( ( candidate ) => candidate.id === state );
				return info === undefined ? '' : editor ? info.editorPseudoClass : info.pseudoClass;
			} )
			.join( '' );
	}

	/** Mirrors LookKey::mediaQuery(). */
	mediaQuery( definition: BlockDefinition ): string | null {
		return this.conditions.length === 0
			? null
			: this.conditions.map( ( condition ) => definition.conditions.find( ( info ) => info.id === condition )?.mediaQuery ?? '' ).join( ' and ' );
	}

	/** Mirrors LookKey::jsonSerialize(). */
	toJSON(): { states?: readonly BlockState[]; when?: readonly MediaCondition[] } {
		return {
			...( this.states.length > 0 ? { states: this.states } : {} ),
			...( this.conditions.length > 0 ? { when: this.conditions } : {} ),
		};
	}
}

/** Mirrors Fb\Blocks\Looks\Look: one look of a source and its values. */
export class Look< V > {
	constructor(
		public readonly key: LookKey,
		public readonly values: V
	) {}

	toJSON(): Record< string, unknown > {
		return { ...this.key.toJSON(), values: this.values };
	}
}
