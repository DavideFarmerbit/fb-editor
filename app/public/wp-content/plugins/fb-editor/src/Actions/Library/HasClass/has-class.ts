/**
 * Runner of Fb\Actions\Library\HasClass: every target has the class (or,
 * with "has it" off, none has).
 */
import { isBoolean, isString, param, type ConditionRunner } from '../../Runtime/types';

export const hasClass: ConditionRunner = {
	type: 'has-class',
	holds( context ) {
		const className = param( context.params, 'className', '', isString );
		const has = param( context.params, 'has', true, isBoolean );
		return context.targets().every( ( target ) => target.classList.contains( className ) === has );
	},
};
