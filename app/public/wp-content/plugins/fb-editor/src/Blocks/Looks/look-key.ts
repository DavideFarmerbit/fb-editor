/**
 * Mirrors Fb\Blocks\Looks\LookKey: which look of a block, the part it
 * styles (null: the block), the states and media conditions it requires (all
 * at once). Selectors and queries come from the definition (computed by PHP).
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import type { BlockDefinition } from '../Definition/block-definition';
import { BlockState } from '../States/BlockState.generated';
import { PATTERN as PART_PATTERN } from '../Parts/PartId.generated';
import type { PartId } from '../Parts/part';
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

	constructor(
		states: readonly BlockState[] = [],
		conditions: readonly MediaCondition[] = [],
		/** The part it styles; null: the block. */
		public readonly part: PartId | null = null
	) {
		this.states = ordered( states, STATE_ORDER );
		this.conditions = ordered( conditions, CONDITION_ORDER );
	}

	/** The same look, on a part (null: the block). */
	onPart( part: PartId | null ): LookKey {
		return new LookKey( this.states, this.conditions, part );
	}

	static readonly BASE = new LookKey();

	static ofState( state: BlockState ): LookKey {
		return new LookKey( [ state ] );
	}

	/** From id(); the base for anything unreadable. */
	static fromId( id: string ): LookKey {
		const slash = id.indexOf( '/' );
		const part = slash === -1 ? null : id.slice( 0, slash );
		const [ states = '', conditions = '' ] = id.slice( slash + 1 ).split( '|' );
		const pick = < T extends string >( text: string, order: readonly T[] ): T[] =>
			text.split( '+' ).flatMap( ( value ) => order.filter( ( candidate ) => candidate === value ) );
		return new LookKey( pick( states, STATE_ORDER ), pick( conditions, CONDITION_ORDER ), part !== null && PART_PATTERN.test( part ) ? ( part as PartId ) : null );
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
			const part = json.has( 'part' ) ? json.get( 'part' ).string() : null;
			if ( part !== null && definition.part( part as PartId ) === undefined ) {
				return null;
			}
			const states = ( json.has( 'states' ) ? json.get( 'states' ).list() : [] ).map( ( value ) => value.string() );
			const conditions = ( json.has( 'when' ) ? json.get( 'when' ).list() : [] ).map( ( value ) => value.string() );
			if (
				! states.every( ( state ) => definition.states.some( ( info ) => info.id === state ) ) ||
				! conditions.every( ( condition ) => CONDITION_ORDER.some( ( candidate ) => candidate === condition ) )
			) {
				return null;
			}
			return new LookKey( states as BlockState[], conditions as MediaCondition[], part as PartId | null );
		} catch ( error ) {
			if ( error instanceof InvalidJson ) {
				return null;
			}
			throw error;
		}
	}

	isBase(): boolean {
		return this.part === null && this.states.length === 0 && this.conditions.length === 0;
	}

	/** No states and no conditions: the base look of the block or of its part. */
	isPlain(): boolean {
		return this.states.length === 0 && this.conditions.length === 0;
	}

	hasStates(): boolean {
		return this.states.length > 0;
	}

	/** Mirrors LookKey::appliesWithin(): same part, states and conditions among the other's. */
	appliesWithin( other: LookKey ): boolean {
		return this.part === other.part && this.states.every( ( state ) => other.states.includes( state ) ) && this.conditions.every( ( condition ) => other.conditions.includes( condition ) );
	}

	equals( other: LookKey ): boolean {
		return this.id() === other.id();
	}

	/** Mirrors LookKey::id(), e.g. "hover+focus|dark", "before/hover"; "" for the base. */
	id(): string {
		const states = this.states.join( '+' );
		const look = this.conditions.length === 0 ? states : `${ states }|${ this.conditions.join( '+' ) }`;
		return this.part === null ? look : `${ this.part }/${ look }`;
	}

	/** Mirrors LookKey::compare(): cascade order, weakest first (the block before its parts). */
	static compare( a: LookKey, b: LookKey ): number {
		const partA = a.part ?? '';
		const partB = b.part ?? '';
		if ( partA !== partB ) {
			// PHP compares strings byte by byte (<=>): same as < on ASCII ids.
			return partA < partB ? -1 : 1;
		}
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

	/** Editor only: the canvas page's classes while previewed with this look's conditions. */
	previewClasses( definition: BlockDefinition ): string[] {
		return this.conditions.flatMap( ( condition ) => definition.conditions.find( ( info ) => info.id === condition )?.previewClass ?? [] );
	}

	/** Mirrors LookKey::mediaQuery(). */
	mediaQuery( definition: BlockDefinition ): string | null {
		return this.conditions.length === 0
			? null
			: this.conditions.map( ( condition ) => definition.conditions.find( ( info ) => info.id === condition )?.mediaQuery ?? '' ).join( ' and ' );
	}

	/** Mirrors LookKey::jsonSerialize(). */
	toJSON(): { part?: PartId; states?: readonly BlockState[]; when?: readonly MediaCondition[] } {
		return {
			...( this.part !== null ? { part: this.part } : {} ),
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
