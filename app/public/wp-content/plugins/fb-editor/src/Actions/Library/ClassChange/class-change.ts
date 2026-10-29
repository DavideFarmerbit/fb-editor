/**
 * Runner of Fb\Actions\Library\ClassChange: adds, removes or toggles a
 * class on the targets; undo does the opposite.
 */
import { isString, param, type EffectRunner, type StepContext } from '../../Runtime/types';

function apply( context: StepContext, reverse: boolean ): void {
	const className = param( context.params, 'className', '', isString );
	const mode = param( context.params, 'mode', 'toggle', isString );
	if ( className === '' ) {
		return;
	}
	for ( const target of context.targets() ) {
		if ( mode === 'toggle' ) {
			target.classList.toggle( className );
		} else {
			target.classList.toggle( className, ( mode === 'add' ) !== reverse );
		}
	}
}

export const classChange: EffectRunner = {
	type: 'class',
	run( context ) {
		apply( context, false );
	},
	undo( context ) {
		apply( context, true );
	},
};
