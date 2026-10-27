/**
 * Mirrors Fb\Blocks\Definition\Input\AspectRatio\AspectRatioInput.
 */
import type { JsonValue } from '../../../../Json/json';
import { AspectRatio } from '../../../../Values/aspect-ratio';

export class AspectRatioInput {
	readonly kind = 'aspect-ratio';

	parse( json: JsonValue ): AspectRatio {
		return new AspectRatio( json.get( 'width' ).number(), json.get( 'height' ).number() );
	}

	static fromJson(): AspectRatioInput {
		return new AspectRatioInput();
	}
}
