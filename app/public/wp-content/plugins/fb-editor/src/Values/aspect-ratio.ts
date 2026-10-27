/**
 * Mirrors Fb\Values\AspectRatio: width:height → `aspect-ratio: 16/9`.
 */
import { InvalidJson } from '../Json/json';
import { formatCssNumber } from './css-number';
import { MAX } from './AspectRatio.generated';
import type { CssValue, ResponsiveValue } from './value';

export class AspectRatio implements CssValue, ResponsiveValue {
	constructor(
		public readonly width: number,
		public readonly height: number
	) {
		for ( const side of [ width, height ] ) {
			if ( ! Number.isFinite( side ) || side <= 0 || side > MAX ) {
				throw new InvalidJson( `Aspect ratio side ${ side } is out of range.` );
			}
		}
	}

	toCss(): string {
		return `${ formatCssNumber( this.width ) }/${ formatCssNumber( this.height ) }`;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { width: number; height: number } {
		return { width: this.width, height: this.height };
	}
}
