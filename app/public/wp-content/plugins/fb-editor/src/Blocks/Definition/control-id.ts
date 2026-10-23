/**
 * Mirrors Fb\Blocks\Definition\ControlId.
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import { PATTERN } from './ControlId.generated';

declare const controlIdBrand: unique symbol;

export type ControlId = string & { readonly [ controlIdBrand ]: true };

export function parseControlId( json: JsonValue ): ControlId {
	const value = json.string();
	if ( ! PATTERN.test( value ) ) {
		throw new InvalidJson( `${ json.path }: invalid control id` );
	}
	return value as ControlId;
}
