/**
 * Mirrors Fb\Blocks\Definition\Condition\*: show-only-if rules evaluated per
 * breakpoint against resolved values.
 */
import { InvalidJson, type JsonValue } from '../../../Json/json';
import { KeywordValue } from '../../../Values/keyword';
import type { ResponsiveValue } from '../../../Values/value';
import { parseControlId, type ControlId } from '../control-id';

/** Every control's effective value at one breakpoint. */
export interface ResolvedValues {
	get( id: ControlId ): ResponsiveValue | null;
}

export interface Condition {
	evaluate( values: ResolvedValues ): boolean;
	references(): readonly ControlId[];
}

function keywordOf( values: ResolvedValues, id: ControlId ): string | null {
	const value = values.get( id );
	return value instanceof KeywordValue ? value.keyword : null;
}

export class Equals implements Condition {
	constructor(
		public readonly control: ControlId,
		public readonly value: string
	) {}

	evaluate( values: ResolvedValues ): boolean {
		return keywordOf( values, this.control ) === this.value;
	}

	references(): ControlId[] {
		return [ this.control ];
	}
}

export class In implements Condition {
	constructor(
		public readonly control: ControlId,
		public readonly values: readonly string[]
	) {}

	evaluate( values: ResolvedValues ): boolean {
		const keyword = keywordOf( values, this.control );
		return keyword !== null && this.values.includes( keyword );
	}

	references(): ControlId[] {
		return [ this.control ];
	}
}

export class HasValue implements Condition {
	constructor( public readonly control: ControlId ) {}

	evaluate( values: ResolvedValues ): boolean {
		return values.get( this.control ) !== null;
	}

	references(): ControlId[] {
		return [ this.control ];
	}
}

export class Not implements Condition {
	constructor( public readonly condition: Condition ) {}

	evaluate( values: ResolvedValues ): boolean {
		return ! this.condition.evaluate( values );
	}

	references(): readonly ControlId[] {
		return this.condition.references();
	}
}

export class All implements Condition {
	constructor( public readonly conditions: readonly Condition[] ) {}

	evaluate( values: ResolvedValues ): boolean {
		return this.conditions.every( ( condition ) => condition.evaluate( values ) );
	}

	references(): ControlId[] {
		return this.conditions.flatMap( ( condition ) => condition.references() );
	}
}

export class Any implements Condition {
	constructor( public readonly conditions: readonly Condition[] ) {}

	evaluate( values: ResolvedValues ): boolean {
		return this.conditions.some( ( condition ) => condition.evaluate( values ) );
	}

	references(): ControlId[] {
		return this.conditions.flatMap( ( condition ) => condition.references() );
	}
}

export function conditionFromJson( json: JsonValue ): Condition {
	const kind = json.get( 'kind' ).string();
	switch ( kind ) {
		case 'equals':
			return new Equals( parseControlId( json.get( 'control' ) ), json.get( 'value' ).scalar() );
		case 'in':
			return new In( parseControlId( json.get( 'control' ) ), json.get( 'values' ).list().map( ( value ) => value.scalar() ) );
		case 'hasValue':
			return new HasValue( parseControlId( json.get( 'control' ) ) );
		case 'not':
			return new Not( conditionFromJson( json.get( 'condition' ) ) );
		case 'all':
			return new All( json.get( 'conditions' ).list().map( conditionFromJson ) );
		case 'any':
			return new Any( json.get( 'conditions' ).list().map( conditionFromJson ) );
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown condition kind "${ kind }"` );
	}
}
