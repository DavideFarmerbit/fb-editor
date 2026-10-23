/**
 * Mirrors Fb\Values\KeywordValue.
 */
import { InvalidJson } from '../Json/json';
import { PATTERN } from './KeywordValue.generated';
import type { CssValue, ResponsiveValue } from './value';

export class KeywordValue implements CssValue, ResponsiveValue {
	constructor( public readonly keyword: string ) {
		if ( ! PATTERN.test( keyword ) ) {
			throw new InvalidJson( `Invalid CSS keyword "${ keyword }".` );
		}
	}

	toCss(): string {
		return this.keyword;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): string {
		return this.keyword;
	}
}
