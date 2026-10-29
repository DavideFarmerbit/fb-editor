/**
 * Mirrors Fb\Blocks\Definition\Input\Target\TargetInput and TargetValue:
 * which block an action works on, itself or another by its anchor.
 *
 *   "self"  |  {"block": "a7k2b9"}
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { PATTERN } from '../../../../Actions/Anchor/BlockAnchor.generated';
import type { ResponsiveValue } from '../../../../Values/value';

export class TargetValue implements ResponsiveValue {
	constructor(
		/** The target's anchor; null: the block itself. */
		public readonly block: string | null = null
	) {}

	isSelf(): boolean {
		return this.block === null;
	}

	inheritFrom(): this {
		return this;
	}

	toJSON(): 'self' | { block: string } {
		return this.block === null ? 'self' : { block: this.block };
	}
}

export class TargetInput {
	readonly kind = 'target';

	parse( json: JsonValue ): TargetValue {
		if ( ! json.isObject() ) {
			if ( json.string() !== 'self' ) {
				throw new InvalidJson( `${ json.path }: expected "self" or a block` );
			}
			return new TargetValue();
		}
		const anchor = json.get( 'block' ).string();
		if ( ! PATTERN.test( anchor ) ) {
			throw new InvalidJson( `${ json.path }.block: not a block anchor` );
		}
		return new TargetValue( anchor );
	}

	static fromJson(): TargetInput {
		return new TargetInput();
	}
}
