/**
 * Mirrors Fb\Blocks\Definition\Input\Length\LengthInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { PresetKind } from '../../../../Presets/presets';
import { AutoLength, CustomLength, lengthFromJson, PresetReference, type Length } from '../../../../Values/length';

/** What a length editor needs to know about its input (Length or Spacing). */
export interface LengthRules {
	readonly allowAuto: boolean;
	readonly allowNegative: boolean;
	/** Theme scale offered as a slider; null for custom values only. */
	readonly scale: PresetKind | null;
	allows( length: Length ): boolean;
}

export class LengthInput implements LengthRules {
	readonly kind = 'length';

	constructor(
		public readonly allowAuto: boolean,
		public readonly allowNegative: boolean,
		public readonly scale: PresetKind | null
	) {}

	allows( length: Length ): boolean {
		if ( length instanceof AutoLength ) {
			return this.allowAuto;
		}
		if ( length instanceof CustomLength ) {
			return this.allowNegative || ! length.isNegative();
		}
		return length instanceof PresetReference && this.scale !== null && length.preset === this.scale;
	}

	parse( json: JsonValue ): Length {
		const length = lengthFromJson( json );
		if ( ! this.allows( length ) ) {
			throw new InvalidJson( `${ json.path }: length not allowed by this input` );
		}
		return length;
	}

	static fromJson( json: JsonValue ): LengthInput {
		return new LengthInput(
			json.get( 'allowAuto' ).boolean(),
			json.get( 'allowNegative' ).boolean(),
			json.get( 'scale' ).nullable( ( scale ) => scale.oneOf( PresetKind ) )
		);
	}
}
