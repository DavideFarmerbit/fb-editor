/**
 * Mirrors Fb\Blocks\Definition\Input\Link\LinkInput (PHP sanitizes the URL
 * again on render).
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { LinkValue } from '../../../../Values/content';

/** http(s), mailto, tel, or a path / anchor on this site. */
export function isAllowedUrl( url: string ): boolean {
	return /^(https?:\/\/|mailto:|tel:|\/|#)/i.test( url.trim() );
}

export class LinkInput {
	readonly kind = 'link';

	parse( json: JsonValue ): LinkValue {
		const url = json.get( 'url' ).string().trim();
		if ( ! isAllowedUrl( url ) ) {
			throw new InvalidJson( `${ json.path }.url: not an allowed link` );
		}
		const flag = ( key: string ): boolean => json.has( key ) && json.get( key ).boolean();
		return new LinkValue( url, flag( 'newTab' ), flag( 'nofollow' ) );
	}

	static fromJson(): LinkInput {
		return new LinkInput();
	}
}
