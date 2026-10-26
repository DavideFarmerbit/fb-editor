/**
 * Mirrors Fb\Blocks\Values\BlockValues and ResolvedValues: one set of
 * control values (a block's base look, or one of its states), parsed through
 * each control's input. Unknown controls and invalid values are dropped.
 * See StatefulValues for the whole `fb` attribute.
 */
import type { BreakpointId, BreakpointList } from '../../Breakpoints/breakpoints';
import type { ResolvedValues } from '../Definition/Condition/conditions';
import { Scope, type BlockDefinition, type Control } from '../Definition/block-definition';
import type { ControlId } from '../Definition/control-id';
import { InvalidJson, JsonValue } from '../../Json/json';
import { Responsive } from '../../Values/responsive';
import type { ResponsiveValue } from '../../Values/value';

export class ResolvedValueMap implements ResolvedValues {
	constructor( private readonly values: ReadonlyMap< string, ResponsiveValue > ) {}

	get( id: ControlId ): ResponsiveValue | null {
		return this.values.get( id ) ?? null;
	}
}

export class BlockValues {
	private constructor(
		private readonly definition: BlockDefinition,
		private readonly breakpointList: BreakpointList,
		private readonly values: ReadonlyMap< string, Responsive >
	) {}

	static empty( definition: BlockDefinition, breakpoints: BreakpointList ): BlockValues {
		return new BlockValues( definition, breakpoints, new Map() );
	}

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

	/** Whether the control has a value at any breakpoint. */
	has( id: ControlId ): boolean {
		return ! this.get( id ).isEmpty();
	}

	/**
	 * A copy with this control's values (every breakpoint) taken from
	 * `source`, e.g. a block's values copied into its type defaults.
	 */
	withControlFrom( control: Control, source: BlockValues ): BlockValues {
		const values = new Map< string, Responsive >();
		for ( const candidate of this.definition.controls ) {
			const next = candidate.id === control.id ? source.get( control.id ) : this.values.get( candidate.id );
			if ( next !== undefined && ! next.isEmpty() ) {
				values.set( candidate.id, next );
			}
		}
		return new BlockValues( this.definition, this.breakpointList, values );
	}

	/** A copy without any value for this control, at every breakpoint. */
	withoutControl( control: Control ): BlockValues {
		const values = new Map( this.values );
		values.delete( control.id );
		return new BlockValues( this.definition, this.breakpointList, values );
	}

	/**
	 * A copy with one control's value set (or cleared, with null) at a
	 * breakpoint. Static controls always write the base breakpoint. Values
	 * stay in definition order, the canonical order PHP also uses.
	 */
	with( control: Control, breakpoint: BreakpointId, value: ResponsiveValue | null ): BlockValues {
		const at = control.scope === Scope.Static ? this.baseBreakpoint() : breakpoint;
		const current = this.get( control.id );
		const updated = value === null ? current.without( at ) : current.with( at, value );

		const values = new Map< string, Responsive >();
		for ( const candidate of this.definition.controls ) {
			const next = candidate.id === control.id ? updated : this.values.get( candidate.id );
			if ( next !== undefined && ! next.isEmpty() ) {
				values.set( candidate.id, next );
			}
		}

		return new BlockValues( this.definition, this.breakpointList, values );
	}

	isEmpty(): boolean {
		return this.values.size === 0;
	}

	/** Where static-scope values live (mirrors BlockValues::baseBreakpoint()). */
	baseBreakpoint(): BreakpointId {
		return this.breakpointList.base.id;
	}

	/** Mirrors BlockValues::onlyStateful(). */
	onlyStateful(): BlockValues {
		const values = new Map< string, Responsive >();
		for ( const control of this.definition.controls ) {
			const value = this.values.get( control.id );
			if ( control.isStateful() && value !== undefined ) {
				values.set( control.id, value );
			}
		}
		return new BlockValues( this.definition, this.breakpointList, values );
	}

	/** Where values are resolved: the breakpoints they are keyed by. */
	breakpoints(): BreakpointList {
		return this.breakpointList;
	}

	/** Mirrors BlockValues::overlay(): `over` wins per control and breakpoint. */
	overlay( over: BlockValues ): BlockValues {
		const values = new Map< string, Responsive >();
		for ( const control of this.definition.controls ) {
			const merged = this.get( control.id ).overlay( over.get( control.id ), this.breakpointList );
			if ( ! merged.isEmpty() ) {
				values.set( control.id, merged );
			}
		}
		return new BlockValues( this.definition, this.breakpointList, values );
	}

	/** Plain JSON for setAttributes(): no class instances in the block store. */
	toAttribute(): unknown {
		return JSON.parse( JSON.stringify( this ) ) as unknown;
	}

	/** Canonical form, as PHP serializes it: static values unwrapped. */
	toJSON(): Record< string, unknown > {
		const json: Record< string, unknown > = {};
		this.values.forEach( ( value, id ) => {
			const control = this.definition.control( id as ControlId );
			json[ id ] = control?.scope === Scope.Static ? value.at( this.breakpointList.base.id ) : value;
		} );
		return json;
	}
}
