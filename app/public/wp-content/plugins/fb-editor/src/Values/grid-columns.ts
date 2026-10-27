/**
 * Mirrors Fb\Values\{GridColumns, GridColumnCount, GridColumnFit}: how a
 * grid makes its columns (one grid-template-columns value).
 */
import { InvalidJson } from '../Json/json';
import { CustomLength } from './length';
import type { CssValue, ResponsiveValue } from './value';

export class GridColumnCount implements CssValue, ResponsiveValue {
	constructor( public readonly count: number ) {
		if ( ! Number.isInteger( count ) || count < 1 ) {
			throw new InvalidJson( 'A grid needs at least one column.' );
		}
	}

	toCss(): string {
		return `repeat(${ this.count },minmax(0,1fr))`;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { kind: 'count'; count: number } {
		return { kind: 'count', count: this.count };
	}
}

export class GridColumnFit implements CssValue, ResponsiveValue {
	constructor( public readonly min: CustomLength ) {
		if ( min.value <= 0 ) {
			throw new InvalidJson( 'A column minimum must be positive.' );
		}
	}

	toCss(): string {
		return `repeat(auto-fit,minmax(min(${ this.min.toCss() },100%),1fr))`;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { kind: 'fit'; min: CustomLength } {
		return { kind: 'fit', min: this.min };
	}
}

export type GridColumns = GridColumnCount | GridColumnFit;
