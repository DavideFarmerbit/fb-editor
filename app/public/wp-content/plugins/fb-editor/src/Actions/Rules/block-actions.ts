/**
 * Mirrors Fb\Actions\BlockActions, ActionRule, Step and ParamValues: a
 * block's anchor and rules, parsed through the catalog the same way PHP does
 * (same drops, same canonical JSON: checked by the parity suite).
 *
 *   {"anchor": "a7k2b9", "actions": [{"on": [step…], "if": […], "do": [step…], "off": [bp…], "undo": "outside"}]}
 */
import { BreakpointsInput } from '../../Blocks/Definition/Input/Breakpoints/breakpoints-input';
import { InvalidJson, JsonValue } from '../../Json/json';
import type { BreakpointSet } from '../../Values/breakpoint-set';
import type { ResponsiveValue } from '../../Values/value';
import { PATTERN as ANCHOR_PATTERN } from '../Anchor/BlockAnchor.generated';
import { ActionKind, type ActionCatalog, type ActionType, type Param } from '../Catalog/action-catalog';
import { MAX_RULES } from './BlockActions.generated';
import { MAX_STEPS } from './ActionRule.generated';
import { Undo } from './Undo.generated';

export { Undo };

/** Runs a parse, giving null for what PHP drops (InvalidJson). */
function attempt< T >( parse: () => T ): T | null {
	try {
		return parse();
	} catch ( error ) {
		if ( error instanceof InvalidJson ) {
			return null;
		}
		throw error;
	}
}

export class ParamValues {
	private constructor( private readonly values: ReadonlyMap< string, ResponsiveValue > ) {}

	static readonly NONE = new ParamValues( new Map() );

	/** Mirrors ParamValues::parse(): null when a required parameter is missing or invalid. */
	static parse( params: readonly Param[], json: JsonValue | null ): ParamValues | null {
		const values = new Map< string, ResponsiveValue >();
		for ( const param of params ) {
			const value = json !== null && json.isObject() && json.has( param.id ) ? attempt( () => param.input.parse( json.get( param.id ) ) ) : null;
			if ( value === null ) {
				if ( param.required ) {
					return null;
				}
				continue;
			}
			values.set( param.id, value );
		}
		return new ParamValues( values );
	}

	get( id: string ): ResponsiveValue | null {
		return this.values.get( id ) ?? null;
	}

	/** A copy with one value set (null: unset), in parameter order. */
	with( params: readonly Param[], id: string, value: ResponsiveValue | null ): ParamValues {
		const values = new Map< string, ResponsiveValue >();
		for ( const param of params ) {
			const next = param.id === id ? value : this.values.get( param.id ) ?? null;
			if ( next !== null ) {
				values.set( param.id, next );
			}
		}
		return new ParamValues( values );
	}

	toJSON(): Record< string, ResponsiveValue > {
		return Object.fromEntries( this.values );
	}
}

export class Step {
	constructor(
		public readonly type: ActionType,
		public readonly params: ParamValues
	) {}

	/** Mirrors Step::parse(). */
	static parse( json: JsonValue, kind: ActionKind, catalog: ActionCatalog ): Step | null {
		if ( ! json.isObject() ) {
			return null;
		}
		const name = attempt( () => json.get( 'type' ).string() );
		const type = name === null ? undefined : catalog.find( kind, name );
		if ( type === undefined ) {
			return null;
		}
		const params = ParamValues.parse( type.params, json.has( 'params' ) ? json.get( 'params' ) : null );
		return params === null ? null : new Step( type, params );
	}

	/** A new step of a type, with no values (its runner's defaults). */
	static of( type: ActionType ): Step {
		return new Step( type, ParamValues.NONE );
	}

	withParam( id: string, value: ResponsiveValue | null ): Step {
		return new Step( this.type, this.params.with( this.type.params, id, value ) );
	}

	/** Whether every required parameter is set (otherwise PHP drops it). */
	isComplete(): boolean {
		return this.type.params.every( ( param ) => ! param.required || this.params.get( param.id ) !== null );
	}

	toJSON(): { type: string; params: ParamValues } {
		return { type: this.type.name, params: this.params };
	}
}

export class ActionRule {
	constructor(
		public readonly triggers: readonly Step[],
		public readonly conditions: readonly Step[],
		public readonly effects: readonly Step[],
		public readonly off: BreakpointSet | null = null,
		public readonly undo: Undo = Undo.Never
	) {}

	/** Mirrors ActionRule::parse(). */
	static parse( json: JsonValue, catalog: ActionCatalog ): ActionRule | null {
		if ( ! json.isObject() ) {
			return null;
		}
		const triggers = ActionRule.steps( json, 'on', ActionKind.Trigger, catalog );
		const effects = ActionRule.steps( json, 'do', ActionKind.Effect, catalog );
		if ( triggers.length === 0 || effects.length === 0 ) {
			return null;
		}
		const off = json.has( 'off' ) ? attempt( () => new BreakpointsInput().parse( json.get( 'off' ) ) ) : null;
		const undo = json.has( 'undo' ) ? attempt( () => json.get( 'undo' ).string() ) : null;
		return new ActionRule(
			triggers,
			ActionRule.steps( json, 'if', ActionKind.Condition, catalog ),
			effects,
			off === null || off.breakpoints.length === 0 ? null : off,
			Object.values( Undo ).find( ( candidate ) => candidate === undo ) ?? Undo.Never
		);
	}

	private static steps( json: JsonValue, key: string, kind: ActionKind, catalog: ActionCatalog ): Step[] {
		if ( ! json.has( key ) ) {
			return [];
		}
		const items = attempt( () => json.get( key ).list() ) ?? [];
		return items
			.map( ( item ) => Step.parse( item, kind, catalog ) )
			.filter( ( step ): step is Step => step !== null )
			.slice( 0, MAX_STEPS );
	}

	/** The steps of one kind. */
	stepsOf( kind: ActionKind ): readonly Step[] {
		switch ( kind ) {
			case ActionKind.Trigger:
				return this.triggers;
			case ActionKind.Condition:
				return this.conditions;
			case ActionKind.Effect:
				return this.effects;
		}
	}

	/** A copy with the steps of one kind replaced. */
	withSteps( kind: ActionKind, steps: readonly Step[] ): ActionRule {
		return new ActionRule(
			kind === ActionKind.Trigger ? steps : this.triggers,
			kind === ActionKind.Condition ? steps : this.conditions,
			kind === ActionKind.Effect ? steps : this.effects,
			this.off,
			this.undo
		);
	}

	withOff( off: BreakpointSet | null ): ActionRule {
		return new ActionRule( this.triggers, this.conditions, this.effects, off === null || off.breakpoints.length === 0 ? null : off, this.undo );
	}

	withUndo( undo: Undo ): ActionRule {
		return new ActionRule( this.triggers, this.conditions, this.effects, this.off, undo );
	}

	/**
	 * What of it survives storage: its complete steps, if a trigger and an
	 * effect remain (otherwise parsing drops the rule: null).
	 */
	savable(): ActionRule | null {
		const complete = ( steps: readonly Step[] ): Step[] => steps.filter( ( step ) => step.isComplete() );
		const rule = new ActionRule( complete( this.triggers ), complete( this.conditions ), complete( this.effects ), this.off, this.undo );
		return rule.triggers.length === 0 || rule.effects.length === 0 ? null : rule;
	}

	/** Mirrors ActionRule::jsonSerialize(). */
	toJSON(): Record< string, unknown > {
		return {
			on: this.triggers,
			...( this.conditions.length > 0 ? { if: this.conditions } : {} ),
			do: this.effects,
			...( this.off !== null ? { off: this.off } : {} ),
			...( this.undo !== Undo.Never ? { undo: this.undo } : {} ),
		};
	}
}

export class BlockActions {
	constructor(
		/** The block's anchor (other rules point at it); null: none yet. */
		public readonly anchor: string | null = null,
		public readonly rules: readonly ActionRule[] = []
	) {}

	static readonly NONE = new BlockActions();

	/** Mirrors BlockActions::fromAttribute(), from the block's raw `fb` attribute. */
	static fromAttribute( attribute: unknown, catalog: ActionCatalog ): BlockActions {
		const root = JsonValue.of( attribute, 'fb' );
		if ( ! root.isObject() ) {
			return BlockActions.NONE;
		}
		const anchor = root.has( 'anchor' ) ? attempt( () => root.get( 'anchor' ).string() ) : null;
		const items = root.has( 'actions' ) ? attempt( () => root.get( 'actions' ).list() ) ?? [] : [];
		return new BlockActions(
			anchor !== null && ANCHOR_PATTERN.test( anchor ) ? anchor : null,
			items
				.map( ( item ) => ActionRule.parse( item, catalog ) )
				.filter( ( rule ): rule is ActionRule => rule !== null )
				.slice( 0, MAX_RULES )
		);
	}

	withRules( rules: readonly ActionRule[] ): BlockActions {
		return new BlockActions( this.anchor, rules );
	}

	withAnchor( anchor: string | null ): BlockActions {
		return new BlockActions( anchor, this.rules );
	}

	/** Its part of the `fb` attribute (merged with the looks by BlockAttribute). */
	toJSON(): { anchor?: string; actions?: readonly ActionRule[] } {
		return {
			...( this.anchor !== null ? { anchor: this.anchor } : {} ),
			...( this.rules.length > 0 ? { actions: this.rules } : {} ),
		};
	}
}
