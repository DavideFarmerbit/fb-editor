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

type Action = SetBreakpointAction | SetSpacingModeAction;

const DEFAULT_STATE: State = {
	breakpoint: settings.breakpoints.base.id,
	spacingModes: {},
};

const actions = {
	setBreakpoint( breakpoint: BreakpointId ): SetBreakpointAction {
		return { type: 'SET_BREAKPOINT', breakpoint };
	},
	setSpacingMode( key: ControlKey, mode: SpacingMode ): SetSpacingModeAction {
		return { type: 'SET_SPACING_MODE', key, mode };
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
};

export const store = createReduxStore( 'fb/editor', {
	reducer( state: State = DEFAULT_STATE, action: Action ): State {
		switch ( action.type ) {
			case 'SET_BREAKPOINT':
				return { ...state, breakpoint: action.breakpoint };
			case 'SET_SPACING_MODE':
				return { ...state, spacingModes: { ...state.spacingModes, [ action.key ]: action.mode } };
		}
		return state;
	},
	actions,
	selectors,
} );

register( store );
