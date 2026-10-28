/**
 * Runner of Fb\Actions\Library\IsOpen: every target is open (or, with
 * "open" off, every target is closed).
 */
import { isOpen } from './open-close';
import { isBoolean, param, type ConditionRunner } from '../runtime/types';

export const isOpenCondition: ConditionRunner = {
	type: 'is-open',
	holds( context ) {
		const open = param( context.params, 'open', true, isBoolean );
		return context.targets().every( ( target ) => isOpen( target ) === open );
	},
};
