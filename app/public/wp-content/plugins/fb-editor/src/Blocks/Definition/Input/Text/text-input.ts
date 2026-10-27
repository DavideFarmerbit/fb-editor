/**
 * Mirrors Fb\Blocks\Definition\Input\Text\TextInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { TextValue } from '../../../../Values/content';
import { MAX_LENGTH } from './TextInput.generated';

export class TextInput {
	readonly kind = 'text';
	readonly maxLength = MAX_LENGTH;

	parse( json: JsonValue ): TextValue {
		const text = json.string().trim();
		if ( text === '' ) {
			throw new InvalidJson( `${ json.path }: empty text` );
		}
		return new TextValue( text.slice( 0, MAX_LENGTH ) );
	}

	static fromJson(): TextInput {
		return new TextInput();
	}
}
