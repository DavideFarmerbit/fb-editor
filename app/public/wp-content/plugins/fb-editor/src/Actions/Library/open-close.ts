/**
 * Runner of Fb\Actions\Library\OpenClose: opens, closes or toggles the
 * targets. Open is the target's class (its Open look styles it) or, on
 * <details> / <dialog>, their own open state. Targets that start closed are
 * hidden by the page's CSS until opened.
 */
import { OPEN_CLASS } from './OpenClose.generated';
import { isBoolean, isString, param, type EffectRunner, type StepContext } from '../runtime/types';

export function isOpen( target: HTMLElement ): boolean {
	return target.classList.contains( OPEN_CLASS ) || target.hasAttribute( 'open' );
}

export function setOpen( target: HTMLElement, open: boolean ): void {
	if ( target instanceof HTMLDetailsElement ) {
		target.open = open;
	}
	if ( target instanceof HTMLDialogElement ) {
		if ( open && ! target.open ) {
			target.show();
		} else if ( ! open && target.open ) {
			target.close();
		}
	}
	target.classList.toggle( OPEN_CLASS, open );
}

function apply( context: StepContext, mode: string ): void {
	for ( const target of context.targets() ) {
		setOpen( target, mode === 'open' || ( mode === 'toggle' && ! isOpen( target ) ) );
	}
}

export const openClose: EffectRunner = {
	type: 'open-close',
	opens: true,
	run( context ) {
		apply( context, param( context.params, 'mode', 'toggle', isString ) );
	},
	undo( context ) {
		apply( context, 'close' );
	},
};

/** Targets marked "starts open" are opened as soon as the rule is set up. */
export function startsOpen( context: StepContext ): boolean {
	return param( context.params, 'startOpen', false, isBoolean );
}
