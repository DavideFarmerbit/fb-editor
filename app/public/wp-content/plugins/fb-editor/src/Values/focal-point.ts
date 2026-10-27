/**
 * Mirrors Fb\Values\FocalPoint: x and y from 0 to 1 → `object-position`.
 */
import { InvalidJson } from '../Json/json';
import { formatCssNumber } from './css-number';
import type { CssValue, ResponsiveValue } from './value';

export class FocalPoint implements CssValue, ResponsiveValue {
	constructor(
		public readonly x: number,
		public readonly y: number
	) {
		for ( const axis of [ x, y ] ) {
			if ( ! Number.isFinite( axis ) || axis < 0 || axis > 1 ) {
				throw new InvalidJson( `Focal point ${ axis } is outside 0–1.` );
			}
		}
	}

	toCss(): string {
		return `${ formatCssNumber( this.x * 100 ) }% ${ formatCssNumber( this.y * 100 ) }%`;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { x: number; y: number } {
		return { x: this.x, y: this.y };
	}
}
