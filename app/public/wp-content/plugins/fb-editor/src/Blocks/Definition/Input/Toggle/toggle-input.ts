/**
 * Mirrors Fb\Blocks\Definition\Input\Toggle\ToggleInput.
 */
import type { JsonValue } from '../../../../Json/json';
import { BooleanValue } from '../../../../Values/boolean';

export class ToggleInput {
	readonly kind = 'toggle';

	parse( json: JsonValue ): BooleanValue {
		return new BooleanValue( json.boolean() );
	}

	static fromJson(): ToggleInput {
		return new ToggleInput();
	}
}
