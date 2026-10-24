/**
 * Mirrors Fb\Blocks\Definition\Condition\*: show-only-if rules evaluated per
 * breakpoint against resolved values. Each can also describe itself in
 * words (labels from the definition) for "needs …" hints in the editor.
 */
import { __, sprintf } from '@wordpress/i18n';

import { InvalidJson, type JsonValue } from '../../../Json/json';
import { KeywordValue } from '../../../Values/keyword';
import type { ResponsiveValue } from '../../../Values/value';
import { parseControlId, type ControlId } from '../control-id';

/** Every control's effective value at one breakpoint. */
export interface ResolvedValues {
	get( id: ControlId ): ResponsiveValue | null;
}

/** Human labels for controls and their options (the block definition). */
export interface ConditionLabels {
	controlLabel( id: ControlId ): string;
	optionLabel( id: ControlId, value: string ): string;
}

export interface Condition {
	evaluate( values: ResolvedValues ): boolean;
	references(): readonly ControlId[];
	/** e.g. "Display: Flex or Grid". */
	describe( labels: ConditionLabels ): string;
}

function keywordOf( values: ResolvedValues, id: ControlId ): string | null {
	const value = values.get( id );
	return value instanceof KeywordValue ? value.keyword : null;
}

function joinWith( parts: readonly string[], separator: string ): string {
	return parts.join( ` ${ separator } ` );
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

	describe( labels: ConditionLabels ): string {
		return `${ labels.controlLabel( this.control ) }: ${ labels.optionLabel( this.control, this.value ) }`;
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

	describe( labels: ConditionLabels ): string {
		const options = this.values.map( ( value ) => labels.optionLabel( this.control, value ) );
		return `${ labels.controlLabel( this.control ) }: ${ joinWith( options, __( 'or', 'fb-editor' ) ) }`;
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

	describe( labels: ConditionLabels ): string {
		/* translators: %s: setting name */
		return sprintf( __( '%s set', 'fb-editor' ), labels.controlLabel( this.control ) );
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

	describe( labels: ConditionLabels ): string {
		/* translators: %s: a condition */
		return sprintf( __( 'not (%s)', 'fb-editor' ), this.condition.describe( labels ) );
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

	describe( labels: ConditionLabels ): string {
		return joinWith(
			this.conditions.map( ( condition ) => condition.describe( labels ) ),
			__( 'and', 'fb-editor' )
		);
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

	describe( labels: ConditionLabels ): string {
		return joinWith(
			this.conditions.map( ( condition ) => condition.describe( labels ) ),
			__( 'or', 'fb-editor' )
		);
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
