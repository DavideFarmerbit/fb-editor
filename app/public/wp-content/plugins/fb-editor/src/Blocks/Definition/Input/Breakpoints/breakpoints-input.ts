/**
 * Mirrors Fb\Blocks\Definition\Input\Breakpoints\BreakpointsInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import type { BreakpointId } from '../../../../Breakpoints/breakpoints';
import { PATTERN } from '../../../../Breakpoints/BreakpointId.generated';
import { BreakpointSet } from '../../../../Values/breakpoint-set';

export class BreakpointsInput {
	readonly kind = 'breakpoints';

	parse( json: JsonValue ): BreakpointSet {
		const ids = json.list().map( ( item ) => {
			const id = item.string();
			if ( ! PATTERN.test( id ) ) {
				throw new InvalidJson( `${ item.path }: invalid breakpoint id "${ id }"` );
			}
			return id as BreakpointId;
		} );
		return new BreakpointSet( ids );
	}

	static fromJson(): BreakpointsInput {
		return new BreakpointsInput();
	}
}
