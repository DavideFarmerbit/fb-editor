/**
 * Mirrors Fb\Blocks\Definition\Input\Preset\PresetInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { PresetKind } from '../../../../Presets/presets';
import { PresetReference } from '../../../../Values/preset-reference';

export class PresetInput {
	readonly kind = 'preset';

	constructor( public readonly preset: PresetKind ) {}

	parse( json: JsonValue ): PresetReference {
		if ( json.get( 'kind' ).string() !== 'preset' ) {
			throw new InvalidJson( `${ json.path }: expected a preset` );
		}
		const reference = new PresetReference( json.get( 'preset' ).oneOf( PresetKind ), json.get( 'slug' ).string() );
		if ( reference.preset !== this.preset ) {
			throw new InvalidJson( `${ json.path }: expected a ${ this.preset } preset` );
		}
		return reference;
	}

	static fromJson( json: JsonValue ): PresetInput {
		return new PresetInput( json.get( 'preset' ).oneOf( PresetKind ) );
	}
}
