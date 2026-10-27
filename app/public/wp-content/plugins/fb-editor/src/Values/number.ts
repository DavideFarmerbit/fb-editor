/**
 * Mirrors Fb\Values\NumberValue: a plain number, no unit.
 */
import { InvalidJson } from '../Json/json';
import { formatCssNumber } from './css-number';
import type { CssValue, ResponsiveValue } from './value';

export class NumberValue implements CssValue, ResponsiveValue {
	public readonly value: number;

	constructor( value: number ) {
		if ( ! Number.isFinite( value ) ) {
			throw new InvalidJson( 'Number must be finite.' );
		}
		// -0 and 0 are the same number: one canonical form (mirrors PHP).
		this.value = value === 0 ? 0 : value;
	}

	toCss(): string {
		return formatCssNumber( this.value );
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): number {
		return this.value;
	}
}
