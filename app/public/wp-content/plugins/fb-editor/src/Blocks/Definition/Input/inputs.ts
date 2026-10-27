/**
 * Mirrors Fb\Blocks\Definition\Input\Input: the union of all inputs and the
 * JSON dispatch by `kind`. Each input lives in its own folder (PHP class,
 * TS class, editor UI, styles).
 */
import { InvalidJson, type JsonValue } from '../../../Json/json';
import { AspectRatioInput } from './AspectRatio/aspect-ratio-input';
import { ColorInput } from './Color/color-input';
import { GridColumnsInput } from './GridColumns/grid-columns-input';
import { LengthInput } from './Length/length-input';
import { NumberInput } from './Number/number-input';
import { PresetInput } from './Preset/preset-input';
import { SelectInput } from './Select/select-input';
import { SpacingInput } from './Spacing/spacing-input';
import { ToggleInput } from './Toggle/toggle-input';

export type Input = SpacingInput | SelectInput | ColorInput | LengthInput | ToggleInput | PresetInput | NumberInput | GridColumnsInput | AspectRatioInput;

export function inputFromJson( json: JsonValue ): Input {
	const kind = json.get( 'kind' ).string();
	switch ( kind ) {
		case 'spacing':
			return SpacingInput.fromJson( json );
		case 'select':
			return SelectInput.fromJson( json );
		case 'color':
			return ColorInput.fromJson( json );
		case 'length':
			return LengthInput.fromJson( json );
		case 'toggle':
			return ToggleInput.fromJson();
		case 'preset':
			return PresetInput.fromJson( json );
		case 'number':
			return NumberInput.fromJson( json );
		case 'grid-columns':
			return GridColumnsInput.fromJson( json );
		case 'aspect-ratio':
			return AspectRatioInput.fromJson();
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown input kind "${ kind }"` );
	}
}
