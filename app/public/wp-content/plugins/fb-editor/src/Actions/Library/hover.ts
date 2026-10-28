/**
 * Runner of Fb\Actions\Library\Hover: the pointer or keyboard focus enters
 * the block; leaving it ends the hover (undo "leave").
 */
import type { TriggerRunner } from '../runtime/types';

export const hover: TriggerRunner = {
	type: 'hover',
	listen( { block }, fire, leave ) {
		block.addEventListener( 'pointerenter', ( event ) => fire( event ) );
		block.addEventListener( 'pointerleave', () => leave() );
		block.addEventListener( 'focusin', ( event ) => fire( event ) );
		block.addEventListener( 'focusout', ( event ) => {
			if ( ! ( event.relatedTarget instanceof Node && block.contains( event.relatedTarget ) ) ) {
				leave();
			}
		} );
	},
};
