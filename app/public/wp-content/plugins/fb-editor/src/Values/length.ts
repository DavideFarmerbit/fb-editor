/**
 * Mirrors Fb\Values\Length and its implementations, plus Lengths::fromJson.
 * Units, bounds and patterns come from the PHP classes (*.generated.ts).
 */
import { InvalidJson, type JsonValue } from '../Json/json';
import { PresetKind } from '../Presets/PresetKind.generated';
import { formatCssNumber } from './css-number';
import { MAX_ABS } from './CustomLength.generated';
import { PresetReference } from './preset-reference';
import { Unit } from './Unit.generated';
import type { CssValue, ResponsiveValue } from './value';

export { PresetReference, Unit };

export class CustomLength implements CssValue, ResponsiveValue {
	public readonly value: number;

	constructor(
		value: number,
		public readonly unit: Unit
	) {
		if ( ! Number.isFinite( value ) || Math.abs( value ) > MAX_ABS ) {
			throw new InvalidJson( `Length ${ value } is out of range.` );
		}
		// -0 and 0 are the same length: one canonical form (mirrors PHP).
		this.value = value === 0 ? 0 : value;
	}

	isNegative(): boolean {
		return this.value < 0;
	}

	toCss(): string {
		const number = formatCssNumber( this.value );
		return number === '0' ? '0' : number + this.unit;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { kind: 'custom'; value: number; unit: Unit } {
		return { kind: 'custom', value: this.value, unit: this.unit };
	}
}

export class AutoLength implements CssValue, ResponsiveValue {
	toCss(): string {
		return 'auto';
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { kind: 'auto' } {
		return { kind: 'auto' };
	}
}

export type Length = PresetReference | CustomLength | AutoLength;

export function lengthFromJson( json: JsonValue ): Length {
	const kind = json.get( 'kind' ).string();
	switch ( kind ) {
		case 'preset':
			return new PresetReference( json.get( 'preset' ).oneOf( PresetKind ), json.get( 'slug' ).string() );
		case 'custom':
			return new CustomLength( json.get( 'value' ).number(), json.get( 'unit' ).oneOf( Unit ) );
		case 'auto':
			return new AutoLength();
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown length kind "${ kind }"` );
	}
}
