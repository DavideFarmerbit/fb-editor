/**
 * Runner of Fb\Actions\Library\ScrollTo: scrolls to the first target,
 * smoothly unless the visitor prefers reduced motion. The offset (a length,
 * as stored: {kind: "custom", value, unit}) leaves room for a fixed header;
 * px, rem and em are converted, anything else is ignored.
 */
import type { EffectRunner } from '../../Runtime/types';

function offsetPx( offset: unknown, target: HTMLElement ): number {
	if ( typeof offset !== 'object' || offset === null ) {
		return 0;
	}
	const { kind, value, unit } = offset as { kind?: unknown; value?: unknown; unit?: unknown };
	if ( kind !== 'custom' || typeof value !== 'number' ) {
		return 0;
	}
	switch ( unit ) {
		case 'px':
			return value;
		case 'rem':
			return value * parseFloat( getComputedStyle( document.documentElement ).fontSize );
		case 'em':
			return value * parseFloat( getComputedStyle( target ).fontSize );
		default:
			return 0;
	}
}

export const scrollTo: EffectRunner = {
	type: 'scroll-to',
	run( context ) {
		const target = context.targets()[ 0 ];
		if ( target === undefined ) {
			return;
		}
		const top = target.getBoundingClientRect().top + window.scrollY - offsetPx( context.params.offset, target );
		window.scrollTo( { top, behavior: matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth' } );
	},
};
