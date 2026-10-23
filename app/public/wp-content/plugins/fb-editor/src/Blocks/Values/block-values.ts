/**
 * Mirrors Fb\Blocks\Values\BlockValues and ResolvedValues: the values of one
 * block instance, parsed from its `fb` attribute through each control's
 * input. Unknown controls and invalid values are dropped.
 */
import type { BreakpointId, BreakpointList } from '../../Breakpoints/breakpoints';
import type { ResolvedValues } from '../Definition/Condition/conditions';
import { Scope, type BlockDefinition, type Control } from '../Definition/block-definition';
import type { ControlId } from '../Definition/control-id';
import { InvalidJson, JsonValue } from '../../Json/json';
import { Responsive } from '../../Values/responsive';
import type { ResponsiveValue } from '../../Values/value';

class ResolvedValueMap implements ResolvedValues {
	constructor( private readonly values: ReadonlyMap< string, ResponsiveValue > ) {}

	get( id: ControlId ): ResponsiveValue | null {
		return this.values.get( id ) ?? null;
	}
}

export class BlockValues {
	private constructor(
		private readonly definition: BlockDefinition,
		private readonly breakpoints: BreakpointList,
		private readonly values: ReadonlyMap< string, Responsive >
	) {}

	static fromAttribute( attribute: unknown, definition: BlockDefinition, breakpoints: BreakpointList ): BlockValues {
		const json = JsonValue.of( attribute, 'fb' );
		const values = new Map< string, Responsive >();

		if ( ! json.isObject() ) {
			return new BlockValues( definition, breakpoints, values );
		}

		for ( const control of definition.controls ) {
			if ( ! json.has( control.id ) ) {
				continue;
			}
			try {
				const value = BlockValues.parseControl( control, json.get( control.id ), breakpoints );
				if ( ! value.isEmpty() ) {
					values.set( control.id, value );
				}
			} catch ( error ) {
				if ( ! ( error instanceof InvalidJson ) ) {
					throw error;
				}
			}
		}

		return new BlockValues( definition, breakpoints, values );
	}

	private static parseControl( control: Control, json: JsonValue, breakpoints: BreakpointList ): Responsive {
		if ( control.scope === Scope.Static ) {
			return json.isNull() ? Responsive.empty() : Responsive.base( breakpoints, control.input.parse( json ) );
		}
		return Responsive.fromJson( json, ( child ) => control.input.parse( child ), breakpoints );
	}

	get( id: ControlId ): Responsive {
		return this.values.get( id ) ?? Responsive.empty();
	}

	isEmpty(): boolean {
		return this.values.size === 0;
	}

	/** Where static-scope values live (mirrors BlockValues::baseBreakpoint()). */
	baseBreakpoint(): BreakpointId {
		return this.breakpoints.base.id;
	}

	resolvedAt( breakpoint: BreakpointId ): ResolvedValues {
		const resolved = new Map< string, ResponsiveValue >();
		this.values.forEach( ( value, id ) => {
			const at = value.resolve( breakpoint, this.breakpoints );
			if ( at !== null ) {
				resolved.set( id, at );
			}
		} );
		return new ResolvedValueMap( resolved );
	}

	/** Canonical form, as PHP serializes it: static values unwrapped. */
	toJSON(): Record< string, unknown > {
		const json: Record< string, unknown > = {};
		this.values.forEach( ( value, id ) => {
			const control = this.definition.control( id as ControlId );
			json[ id ] = control?.scope === Scope.Static ? value.at( this.breakpoints.base.id ) : value;
		} );
		return json;
	}
}
