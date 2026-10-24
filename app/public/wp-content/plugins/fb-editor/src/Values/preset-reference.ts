/**
 * Mirrors Fb\Values\PresetReference: a reference to a theme preset (a
 * spacing step, a palette color…). Which kinds are acceptable is the
 * input's call.
 */
import { InvalidJson } from '../Json/json';
import type { PresetKind, Presets } from '../Presets/presets';
import { SLUG_PATTERN } from './PresetReference.generated';
import type { CssValue, ResponsiveValue } from './value';

export class PresetReference implements CssValue, ResponsiveValue {
	constructor(
		public readonly preset: PresetKind,
		public readonly slug: string
	) {
		if ( ! SLUG_PATTERN.test( slug ) ) {
			throw new InvalidJson( `Invalid preset slug "${ slug }".` );
		}
	}

	toCss( presets: Presets ): string {
		return `var(${ presets.variable( this.preset, this.slug ) })`;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { kind: 'preset'; preset: PresetKind; slug: string } {
		return { kind: 'preset', preset: this.preset, slug: this.slug };
	}
}
