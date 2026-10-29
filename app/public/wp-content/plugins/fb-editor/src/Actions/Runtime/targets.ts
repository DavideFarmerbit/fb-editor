/**
 * Finds the blocks a target parameter points at: the block itself, or the
 * blocks with an anchor class. The nearest first: inside the block, then in
 * each ancestor outwards, so in a repeated card (a query loop) each card's
 * button finds its own panel.
 */
import { CLASS_PREFIX } from '../Anchor/BlockAnchor.generated';

/** `{ block: "a7k2b9" }` → its anchor; "self", unset or anything else → null. */
function anchorOf( target: unknown ): string | null {
	if ( typeof target === 'object' && target !== null && 'block' in target ) {
		const block = ( target as { block: unknown } ).block;
		return typeof block === 'string' ? block : null;
	}
	return null;
}

export function resolveTargets( block: HTMLElement, target: unknown ): HTMLElement[] {
	const anchor = anchorOf( target );
	if ( anchor === null ) {
		return [ block ];
	}

	const selector = `.${ CSS.escape( CLASS_PREFIX + anchor ) }`;
	for ( let scope: HTMLElement | null = block; scope !== null; scope = scope.parentElement ) {
		const found = scope.querySelectorAll< HTMLElement >( selector );
		if ( found.length > 0 ) {
			return [ ...found ];
		}
	}
	return [];
}
