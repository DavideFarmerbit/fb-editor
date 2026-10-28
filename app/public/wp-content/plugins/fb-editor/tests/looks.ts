/**
 * Raw `{"looks": […]}` values for tests (mirrors Fb\Tests\Fixtures\TestLooks):
 * a base plus per-state values, the common case.
 */
export function looks( base: unknown, states: Record< string, object > = {} ): { looks?: unknown[] } {
	const list: unknown[] = [];
	if ( typeof base === 'object' && base !== null && Object.keys( base ).length > 0 ) {
		list.push( { values: base } );
	}
	for ( const [ state, values ] of Object.entries( states ) ) {
		list.push( { states: [ state ], values } );
	}
	return list.length === 0 ? {} : { looks: list };
}
