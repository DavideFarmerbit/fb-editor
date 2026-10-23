/**
 * `fb/editor` store: editor-wide state shared by all fb UI.
 */
import { createReduxStore, register } from '@wordpress/data';

import type { Breakpoint, BreakpointId, BreakpointList } from './breakpoints/breakpoint';
import { settings } from './settings';

interface State {
	readonly breakpoint: BreakpointId;
}

interface SetBreakpointAction {
	readonly type: 'SET_BREAKPOINT';
	readonly breakpoint: BreakpointId;
}

type Action = SetBreakpointAction;

const DEFAULT_STATE: State = {
	breakpoint: settings.breakpoints.base.id,
};

const actions = {
	setBreakpoint( breakpoint: BreakpointId ): SetBreakpointAction {
		return { type: 'SET_BREAKPOINT', breakpoint };
	},
};

const selectors = {
	getBreakpoints(): BreakpointList {
		return settings.breakpoints;
	},
	getBreakpoint( state: State ): Breakpoint {
		return settings.breakpoints.get( state.breakpoint ) ?? settings.breakpoints.base;
	},
};

export const store = createReduxStore( 'fb/editor', {
	reducer( state: State = DEFAULT_STATE, action: Action ): State {
		switch ( action.type ) {
			case 'SET_BREAKPOINT':
				return { ...state, breakpoint: action.breakpoint };
		}
		return state;
	},
	actions,
	selectors,
} );

register( store );
