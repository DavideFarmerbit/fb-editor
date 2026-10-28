/**
 * Mirrors Fb\Blocks\Definition\Input\Content\ContentInput: what a ::before /
 * ::after or marker shows. Empty is a value (an empty box); control
 * characters are dropped; at most MAX_LENGTH characters (code points, as
 * PHP's mb_substr counts).
 */
import type { JsonValue } from '../../../../Json/json';
import { TextValue } from '../../../../Values/content';
import { MAX_LENGTH } from './ContentInput.generated';

export class ContentInput {
	readonly kind = 'content';
	readonly maxLength = MAX_LENGTH;

	parse( json: JsonValue ): TextValue {
		return ContentInput.clean( json.string() );
	}

	/** What a typed text becomes (same rules as parsing). */
	static clean( text: string ): TextValue {
		// eslint-disable-next-line no-control-regex
		return new TextValue( Array.from( text.replace( /[\u0000-\u001F\u007F]/g, '' ) ).slice( 0, MAX_LENGTH ).join( '' ) );
	}

	static fromJson(): ContentInput {
		return new ContentInput();
	}
}
