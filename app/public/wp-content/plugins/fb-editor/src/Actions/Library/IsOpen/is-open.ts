/**
 * Runner of Fb\Actions\Library\IsOpen: every target is open (or, with
 * "open" off, every target is closed).
 */
import { isBoolean, param, type ConditionRunner } from '../../Runtime/types';
import { isOpen } from '../OpenClose/open-close';

export const isOpenCondition: ConditionRunner = {
	type: 'is-open',
	holds( context ) {
		const open = param( context.params, 'open', true, isBoolean );
		return context.targets().every( ( target ) => isOpen( target ) === open );
	},
};
