/**
 * Mirrors Fb\Blocks\Definition\Input\Input: the union of all inputs and the
 * JSON dispatch by `kind`. Each input lives in its own folder (PHP class,
 * TS class, editor UI, styles).
 */
import { InvalidJson, type JsonValue } from '../../../Json/json';
import { SelectInput } from './Select/select-input';
import { SpacingInput } from './Spacing/spacing-input';

export type Input = SpacingInput | SelectInput;

export function inputFromJson( json: JsonValue ): Input {
	const kind = json.get( 'kind' ).string();
	switch ( kind ) {
		case 'spacing':
			return SpacingInput.fromJson( json );
		case 'select':
			return SelectInput.fromJson( json );
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown input kind "${ kind }"` );
	}
}
