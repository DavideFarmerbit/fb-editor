/**
 * Mirrors Fb\Blocks\Definition\Input\Input: the union of all inputs and the
 * JSON dispatch by `kind`. Each input lives in its own folder (PHP class,
 * TS class, editor UI, styles).
 */
import { InvalidJson, type JsonValue } from '../../../Json/json';
import { ColorInput } from './Color/color-input';
import { LengthInput } from './Length/length-input';
import { SelectInput } from './Select/select-input';
import { SpacingInput } from './Spacing/spacing-input';
import { ToggleInput } from './Toggle/toggle-input';

export type Input = SpacingInput | SelectInput | ColorInput | LengthInput | ToggleInput;

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
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown input kind "${ kind }"` );
	}
}
