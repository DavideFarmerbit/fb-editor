/**
 * Mirrors Fb\Values\Color, CustomColor and Colors::fromJson.
 */
import { InvalidJson, type JsonValue } from '../Json/json';
import { PresetKind } from '../Presets/presets';
import { PATTERN } from './CustomColor.generated';
import { PresetReference } from './preset-reference';
import type { CssValue, ResponsiveValue } from './value';

/** Hex only, stored lowercase: one canonical form per color. */
export class CustomColor implements CssValue, ResponsiveValue {
	public readonly hex: string;

	constructor( hex: string ) {
		if ( ! PATTERN.test( hex ) ) {
			throw new InvalidJson( `Invalid color "${ hex }": use a hex color.` );
		}
		this.hex = hex.toLowerCase();
	}

	toCss(): string {
		return this.hex;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { kind: 'custom'; value: string } {
		return { kind: 'custom', value: this.hex };
	}
}

export type Color = PresetReference | CustomColor;

export function colorFromJson( json: JsonValue ): Color {
	const kind = json.get( 'kind' ).string();
	switch ( kind ) {
		case 'preset':
			return new PresetReference( json.get( 'preset' ).oneOf( PresetKind ), json.get( 'slug' ).string() );
		case 'custom':
			return new CustomColor( json.get( 'value' ).string() );
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown color kind "${ kind }"` );
	}
}
