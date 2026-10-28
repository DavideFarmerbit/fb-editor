/**
 * Runner of Fb\Actions\Library\Click: a click on the block's handle, or
 * Enter / Space on it. Clicks on other links and buttons inside the block
 * stay theirs.
 */
import { isNativeControl, type TriggerRunner } from '../runtime/types';

export const click: TriggerRunner = {
	type: 'click',
	interactive: true,
	listen( { handle }, fire ) {
		handle.addEventListener( 'click', ( event ) => {
			const inner = event.target instanceof Element ? event.target.closest( 'a,button' ) : null;
			if ( inner !== null && inner !== handle && handle.contains( inner ) ) {
				return;
			}
			fire( event );
		} );
		handle.addEventListener( 'keydown', ( event ) => {
			// Real links and buttons already turn these keys into clicks.
			if ( ( event.key === 'Enter' || event.key === ' ' ) && ! isNativeControl( handle ) ) {
				event.preventDefault();
				fire( event );
			}
		} );
	},
};
