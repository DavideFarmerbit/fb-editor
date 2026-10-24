/**
 * Mirrors Fb\Blocks\Definition\Input\Color\ColorInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { PresetKind } from '../../../../Presets/presets';
import { colorFromJson, CustomColor, type Color } from '../../../../Values/color';
import { PresetReference } from '../../../../Values/preset-reference';

export class ColorInput {
	readonly kind = 'color';

	constructor( public readonly allowCustom: boolean ) {}

	allows( color: Color ): boolean {
		if ( color instanceof PresetReference ) {
			return color.preset === PresetKind.Color;
		}
		return color instanceof CustomColor && this.allowCustom;
	}

	parse( json: JsonValue ): Color {
		const color = colorFromJson( json );
		if ( ! this.allows( color ) ) {
			throw new InvalidJson( `${ json.path }: color not allowed by this input` );
		}
		return color;
	}

	static fromJson( json: JsonValue ): ColorInput {
		return new ColorInput( json.get( 'allowCustom' ).boolean() );
	}
}
