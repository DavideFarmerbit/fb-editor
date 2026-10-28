/**
 * Runner of Fb\Actions\Library\EntersView: the block scrolls into view, the
 * first time only unless "every time" is on.
 */
import { isBoolean, param, type TriggerRunner } from '../runtime/types';

export const entersView: TriggerRunner = {
	type: 'enters-view',
	listen( { block, params }, fire, leave ) {
		const everyTime = param( params, 'everyTime', false, isBoolean );
		const observer = new IntersectionObserver( ( entries ) => {
			for ( const entry of entries ) {
				if ( entry.isIntersecting ) {
					fire( null );
					if ( ! everyTime ) {
						observer.disconnect();
					}
				} else if ( everyTime ) {
					leave();
				}
			}
		} );
		observer.observe( block );
	},
};
