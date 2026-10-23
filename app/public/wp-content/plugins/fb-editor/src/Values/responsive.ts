/**
 * Mirrors Fb\Values\Responsive: a value per breakpoint id, desktop-first.
 */
import type { BreakpointId, BreakpointList } from '../Breakpoints/breakpoints';
import { InvalidJson, type JsonValue } from '../Json/json';
import type { ResponsiveValue } from './value';

export class Responsive< T extends ResponsiveValue = ResponsiveValue > {
	private constructor( private readonly values: ReadonlyMap< string, T > ) {}

	static empty< V extends ResponsiveValue = never >(): Responsive< V > {
		return new Responsive< V >( new Map() );
	}

	static base< V extends ResponsiveValue >( breakpoints: BreakpointList, value: V ): Responsive< V > {
		return new Responsive( new Map( [ [ breakpoints.base.id, value ] ] ) );
	}

	with< V extends ResponsiveValue >( breakpoint: BreakpointId, value: V ): Responsive< T | V > {
		return new Responsive< T | V >( new Map< string, T | V >( this.values ).set( breakpoint, value ) );
	}

	/** Returns a copy without the value at this breakpoint. */
	without( breakpoint: BreakpointId ): Responsive< T > {
		const values = new Map( this.values );
		values.delete( breakpoint );
		return new Responsive( values );
	}

	/** The value set at exactly this breakpoint. */
	at( breakpoint: BreakpointId ): T | null {
		return this.values.get( breakpoint ) ?? null;
	}

	/** The effective value: walks from the base down, inheriting unset parts. */
	resolve( breakpoint: BreakpointId, breakpoints: BreakpointList ): T | null {
		let resolved: T | null = null;

		for ( const candidate of breakpoints ) {
			const own = this.at( candidate.id );
			if ( own !== null ) {
				resolved = resolved === null ? own : ( own.inheritFrom( resolved ) as T );
			}
			if ( candidate.id === breakpoint ) {
				return resolved;
			}
		}

		return null;
	}

	isEmpty(): boolean {
		return this.values.size === 0;
	}

	/**
	 * Unknown breakpoint ids and invalid values are dropped one by one.
	 * Throws only when the container itself isn't an object.
	 */
	static fromJson< V extends ResponsiveValue >(
		json: JsonValue,
		parseValue: ( json: JsonValue ) => V,
		breakpoints: BreakpointList
	): Responsive< V > {
		const values = new Map< string, V >();

		for ( const [ id, child ] of json.properties() ) {
			if ( breakpoints.get( id as BreakpointId ) === undefined || child.isNull() ) {
				continue;
			}
			try {
				values.set( id, parseValue( child ) );
			} catch ( error ) {
				if ( ! ( error instanceof InvalidJson ) ) {
					throw error;
				}
			}
		}

		return new Responsive( values );
	}

	toJSON(): Record< string, T > {
		return Object.fromEntries( this.values );
	}
}
