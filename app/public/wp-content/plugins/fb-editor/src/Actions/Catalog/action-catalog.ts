/**
 * Mirrors Fb\Actions\ActionCatalog, ActionType and Param: the triggers,
 * conditions and effects rules may use, as PHP declares them (labels,
 * parameters and their inputs). Built only from the editor settings.
 */
import { ClassNameInput } from '../../Blocks/Definition/Input/ClassName/class-name-input';
import { inputFromJson, type Input } from '../../Blocks/Definition/Input/inputs';
import { TargetInput } from '../../Blocks/Definition/Input/Target/target-input';
import type { JsonValue } from '../../Json/json';
import { ActionKind } from './ActionKind.generated';

export { ActionKind };

/** What a parameter is edited with: any block input, or an action-only one. */
export type ParamInput = Input | TargetInput | ClassNameInput;

function paramInputFromJson( json: JsonValue ): ParamInput {
	switch ( json.get( 'kind' ).string() ) {
		case 'target':
			return TargetInput.fromJson();
		case 'class-name':
			return ClassNameInput.fromJson();
		default:
			return inputFromJson( json );
	}
}

export class Param {
	constructor(
		public readonly id: string,
		public readonly label: string,
		public readonly input: ParamInput,
		/** Without it the step is dropped (mirrors Param::$required). */
		public readonly required: boolean
	) {}

	static fromJson( json: JsonValue ): Param {
		return new Param( json.get( 'id' ).string(), json.get( 'label' ).string(), paramInputFromJson( json.get( 'input' ) ), json.get( 'required' ).boolean() );
	}
}

export class ActionType {
	constructor(
		public readonly kind: ActionKind,
		/** Stored in rules; the runner's name. */
		public readonly name: string,
		public readonly label: string,
		public readonly params: readonly Param[]
	) {}

	static fromJson( json: JsonValue ): ActionType {
		return new ActionType( json.get( 'kind' ).oneOf( ActionKind ), json.get( 'name' ).string(), json.get( 'label' ).string(), json.get( 'params' ).list().map( Param.fromJson ) );
	}
}

export class ActionCatalog {
	constructor( public readonly types: readonly ActionType[] ) {}

	/** No types: every stored rule is dropped (tests, or no actions at all). */
	static readonly EMPTY = new ActionCatalog( [] );

	find( kind: ActionKind, name: string ): ActionType | undefined {
		return this.types.find( ( type ) => type.kind === kind && type.name === name );
	}

	ofKind( kind: ActionKind ): ActionType[] {
		return this.types.filter( ( type ) => type.kind === kind );
	}

	static fromJson( json: JsonValue ): ActionCatalog {
		return new ActionCatalog( json.list().map( ActionType.fromJson ) );
	}
}
