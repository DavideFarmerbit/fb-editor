/**
 * Runner of Fb\Actions\Library\PageLoad: once, when the page is ready (the
 * script is deferred: it already is when this runs).
 */
import type { TriggerRunner } from '../../Runtime/types';

export const pageLoad: TriggerRunner = {
	type: 'page-load',
	listen( _context, fire ) {
		fire( null );
	},
};
