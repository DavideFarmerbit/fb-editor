/**
 * Mirrors Fb\Blocks\Definition\ElementId.
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import { PATTERN } from './ElementId.generated';

declare const elementIdBrand: unique symbol;

export type ElementId = string & { readonly [ elementIdBrand ]: true };

export function parseElementId( json: JsonValue ): ElementId {
	const value = json.string();
	if ( ! PATTERN.test( value ) ) {
		throw new InvalidJson( `${ json.path }: invalid element id` );
	}
	return value as ElementId;
}
