/**
 * Mirrors Fb\Blocks\Definition\Input\FocalPoint\FocalPointInput.
 */
import type { JsonValue } from '../../../../Json/json';
import { FocalPoint } from '../../../../Values/focal-point';
import { parseControlId, type ControlId } from '../../control-id';

export class FocalPointInput {
	readonly kind = 'focal-point';

	constructor(
		/** The block's media control whose image the picker shows. */
		public readonly media: ControlId
	) {}

	parse( json: JsonValue ): FocalPoint {
		return new FocalPoint( json.get( 'x' ).number(), json.get( 'y' ).number() );
	}

	static fromJson( json: JsonValue ): FocalPointInput {
		return new FocalPointInput( parseControlId( json.get( 'media' ) ) );
	}
}
