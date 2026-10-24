/**
 * Mirrors Fb\Blocks\Definition\Input\Spacing\SpacingInput. `parse()` and
 * `allows()` apply the same rules as PHP.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { PresetKind } from '../../../../Presets/presets';
import { BoxValue, Side } from '../../../../Values/box';
import { AutoLength, CustomLength, lengthFromJson, PresetLength, type Length } from '../../../../Values/length';
import { SpacingMode } from './SpacingMode.generated';

export { SpacingMode };

export class SpacingInput {
	readonly kind = 'spacing';

	constructor(
		public readonly allowAuto: boolean,
		public readonly allowNegative: boolean,
		public readonly modes: readonly SpacingMode[]
	) {}

	allows( length: Length ): boolean {
		if ( length instanceof AutoLength ) {
			return this.allowAuto;
		}
		if ( length instanceof CustomLength ) {
			return this.allowNegative || ! length.isNegative();
		}
		return length instanceof PresetLength && length.preset === PresetKind.Spacing;
	}

	/** Invalid sides are dropped one by one; a box with no valid side is rejected. */
	parse( json: JsonValue ): BoxValue {
		if ( ! json.isObject() ) {
			throw new InvalidJson( `${ json.path }: expected an object` );
		}

		const side = ( name: Side ): Length | null => {
			if ( ! json.has( name ) || json.get( name ).isNull() ) {
				return null;
			}
			try {
				const length = lengthFromJson( json.get( name ) );
				return this.allows( length ) ? length : null;
			} catch ( error ) {
				if ( error instanceof InvalidJson ) {
					return null;
				}
				throw error;
			}
		};

		const box = new BoxValue( side( Side.Top ), side( Side.Right ), side( Side.Bottom ), side( Side.Left ) );
		if ( box.isEmpty() ) {
			throw new InvalidJson( `${ json.path }: no valid side` );
		}
		return box;
	}

	static fromJson( json: JsonValue ): SpacingInput {
		return new SpacingInput(
			json.get( 'allowAuto' ).boolean(),
			json.get( 'allowNegative' ).boolean(),
			json.get( 'modes' ).list().map( ( mode ) => mode.oneOf( SpacingMode ) )
		);
	}
}
