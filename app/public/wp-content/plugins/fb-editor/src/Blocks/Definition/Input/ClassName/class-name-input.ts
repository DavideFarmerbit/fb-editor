/**
 * Mirrors Fb\Blocks\Definition\Input\ClassName\ClassNameInput: one class
 * name an action adds or checks (lowercase letters, digits, dashes).
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { KeywordValue } from '../../../../Values/keyword';
import { PATTERN } from '../../../../Values/KeywordValue.generated';

export class ClassNameInput {
	readonly kind = 'class-name';

	parse( json: JsonValue ): KeywordValue {
		const className = json.string().trim();
		if ( ! PATTERN.test( className ) ) {
			throw new InvalidJson( `${ json.path }: not a class name (lowercase letters, digits, -)` );
		}
		return new KeywordValue( className );
	}

	static fromJson(): ClassNameInput {
		return new ClassNameInput();
	}
}
