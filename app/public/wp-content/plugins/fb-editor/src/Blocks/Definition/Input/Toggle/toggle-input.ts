/**
 * Mirrors Fb\Blocks\Definition\Input\Toggle\ToggleInput.
 */
import type { JsonValue } from '../../../../Json/json';
import { BooleanValue } from '../../../../Values/boolean';

export class ToggleInput {
	readonly kind = 'toggle';

	constructor(
		/** What applies while nothing is stored. */
		public readonly fallback: boolean = false
	) {}

	parse( json: JsonValue ): BooleanValue {
		return new BooleanValue( json.boolean() );
	}

	static fromJson( json: JsonValue ): ToggleInput {
		return new ToggleInput( json.get( 'fallback' ).boolean() );
	}
}
