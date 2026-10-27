/**
 * Mirrors Fb\Blocks\Definition\Input\Media\MediaInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { MediaKind, MediaValue } from '../../../../Values/content';

export class MediaInput {
	readonly kind = 'media';

	constructor( public readonly kinds: readonly MediaKind[] ) {}

	parse( json: JsonValue ): MediaValue {
		const kind = json.get( 'kind' ).oneOf( MediaKind );
		if ( ! this.kinds.includes( kind ) ) {
			throw new InvalidJson( `${ json.path }.kind: media kind not allowed` );
		}
		const id = json.get( 'id' ).int();
		if ( id < 1 ) {
			throw new InvalidJson( `${ json.path }.id: invalid attachment id` );
		}
		return new MediaValue( id, kind );
	}

	static fromJson( json: JsonValue ): MediaInput {
		return new MediaInput( json.get( 'kinds' ).list().map( ( kind ) => kind.oneOf( MediaKind ) ) );
	}
}
