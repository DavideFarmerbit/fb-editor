/**
 * `fb/editor` store: editor-wide UI state shared by all fb UI. In memory
 * only: nothing here survives a page reload, by design.
 */
import { createReduxStore, register } from '@wordpress/data';

import type { SpacingMode } from '../Blocks/Definition/Input/Spacing/spacing-input';
import type { Breakpoint, BreakpointId, BreakpointList } from '../Breakpoints/breakpoints';
import { settings } from './settings';

/**
 * UI memory is per block type + control (e.g. "fb/section/padding"), so
 * every block of a type opens the same way.
 */
export type ControlKey = `${ string }/${ string }`;

export function controlKey( blockName: string, controlId: string ): ControlKey {
	return `${ blockName }/${ controlId }`;
}

interface State {
	readonly breakpoint: BreakpointId;
	/** Redux state must be plain data: keyed by ControlKey. */
	readonly spacingModes: Readonly< Record< ControlKey, SpacingMode > >;
	/**
	 * Controls added from the "Add setting" menu, per memory key (a block's
	 * clientId, or a block type in the defaults sidebar). Keeps an added
	 * control visible while it's still empty.
	 */
	readonly addedControls: Readonly< Record< string, readonly string[] > >;
}

interface SetBreakpointAction {
	readonly type: 'SET_BREAKPOINT';
	readonly breakpoint: BreakpointId;
}

interface SetSpacingModeAction {
	readonly type: 'SET_SPACING_MODE';
	readonly key: ControlKey;
	readonly mode: SpacingMode;
}

interface AddControlAction {
	readonly type: 'ADD_CONTROL';
	readonly key: string;
	readonly controlId: string;
}

interface ForgetControlAction {
	readonly type: 'FORGET_CONTROL';
	readonly key: string;
	readonly controlId: string;
}

type Action = SetBreakpointAction | SetSpacingModeAction | AddControlAction | ForgetControlAction;

const NONE: readonly string[] = [];

const DEFAULT_STATE: State = {
	breakpoint: settings.breakpoints.base.id,
	spacingModes: {},
	addedControls: {},
};

const actions = {
	setBreakpoint( breakpoint: BreakpointId ): SetBreakpointAction {
		return { type: 'SET_BREAKPOINT', breakpoint };
	},
	setSpacingMode( key: ControlKey, mode: SpacingMode ): SetSpacingModeAction {
		return { type: 'SET_SPACING_MODE', key, mode };
	},
	addControl( key: string, controlId: string ): AddControlAction {
		return { type: 'ADD_CONTROL', key, controlId };
	},
	forgetControl( key: string, controlId: string ): ForgetControlAction {
		return { type: 'FORGET_CONTROL', key, controlId };
	},
};

const selectors = {
	getBreakpoints(): BreakpointList {
		return settings.breakpoints;
	},
	getBreakpoint( state: State ): Breakpoint {
		return settings.breakpoints.get( state.breakpoint ) ?? settings.breakpoints.base;
	},
	/** The mode the user last picked for this control, if any. */
	getSpacingMode( state: State, key: ControlKey ): SpacingMode | null {
		return state.spacingModes[ key ] ?? null;
	},
	getAddedControls( state: State, key: string ): readonly string[] {
		return state.addedControls[ key ] ?? NONE;
	},
};

export const store = createReduxStore( 'fb/editor', {
	reducer( state: State = DEFAULT_STATE, action: Action ): State {
		switch ( action.type ) {
			case 'SET_BREAKPOINT':
				return { ...state, breakpoint: action.breakpoint };
			case 'SET_SPACING_MODE':
				return { ...state, spacingModes: { ...state.spacingModes, [ action.key ]: action.mode } };
			case 'ADD_CONTROL': {
				const added = state.addedControls[ action.key ] ?? NONE;
				return added.includes( action.controlId )
					? state
					: { ...state, addedControls: { ...state.addedControls, [ action.key ]: [ ...added, action.controlId ] } };
			}
			case 'FORGET_CONTROL': {
				const added = state.addedControls[ action.key ] ?? NONE;
				return {
					...state,
					addedControls: {
						...state.addedControls,
						[ action.key ]: added.filter( ( id ) => id !== action.controlId ),
					},
				};
			}
		}
		return state;
	},
	actions,
	selectors,
} );

register( store );
