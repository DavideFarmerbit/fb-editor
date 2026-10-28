/**
 * `fb/editor` store: editor-wide UI state shared by all fb UI. In memory
 * only: nothing here survives a page reload, by design.
 */
import { createReduxStore, register } from '@wordpress/data';

import type { SpacingMode } from '../Blocks/Definition/Input/Spacing/spacing-input';
import type { SidebarView } from '../Blocks/Inspector/inspector';
import type { LookKey, MediaCondition } from '../Blocks/Looks/look-key';
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
	/**
	 * The look being edited (its LookKey id), per memory key (absent: the
	 * base). A selected block in a state also previews it on the canvas.
	 */
	readonly inspectedLooks: Readonly< Record< string, string > >;
	/** "Add setting" also lists settings the block can't use right now. */
	readonly showUnavailableSettings: boolean;
	/**
	 * Blocks hidden at the previewed breakpoint ("Hide on") stay in the
	 * canvas, faded, so they can be selected. Off: hidden as on the page.
	 */
	readonly showHiddenBlocks: boolean;
	/** Block sidebars list only settings or only styles; null: both (the view filter switches). */
	readonly sidebarView: SidebarView | null;
	/**
	 * The device the canvas is previewed as (View menu): looks requiring
	 * these media conditions apply there, others don't, whatever the
	 * editing device is.
	 */
	readonly previewConditions: readonly MediaCondition[];
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

interface InspectLookAction {
	readonly type: 'INSPECT_LOOK';
	readonly key: string;
	/** LookKey id ("" for the base). */
	readonly look: string;
}

interface ShowUnavailableSettingsAction {
	readonly type: 'SHOW_UNAVAILABLE_SETTINGS';
	readonly show: boolean;
}

interface SetSidebarViewAction {
	readonly type: 'SET_SIDEBAR_VIEW';
	readonly view: SidebarView | null;
}

interface ShowHiddenBlocksAction {
	readonly type: 'SHOW_HIDDEN_BLOCKS';
	readonly show: boolean;
}

interface PreviewConditionsAction {
	readonly type: 'PREVIEW_CONDITIONS';
	readonly conditions: readonly MediaCondition[];
}

type Action = PreviewConditionsAction | SetSidebarViewAction | ShowHiddenBlocksAction | SetBreakpointAction | SetSpacingModeAction | AddControlAction | ForgetControlAction | InspectLookAction | ShowUnavailableSettingsAction;

const NONE: readonly string[] = [];

const DEFAULT_STATE: State = {
	breakpoint: settings.breakpoints.base.id,
	spacingModes: {},
	addedControls: {},
	inspectedLooks: {},
	showUnavailableSettings: true,
	showHiddenBlocks: true,
	sidebarView: null,
	previewConditions: [],
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
	/** null: back to the base look. */
	inspectLook( key: string, look: LookKey ): InspectLookAction {
		return { type: 'INSPECT_LOOK', key, look: look.id() };
	},
	showUnavailableSettings( show: boolean ): ShowUnavailableSettingsAction {
		return { type: 'SHOW_UNAVAILABLE_SETTINGS', show };
	},
	/** null: settings and styles. */
	setSidebarView( view: SidebarView | null ): SetSidebarViewAction {
		return { type: 'SET_SIDEBAR_VIEW', view };
	},
	showHiddenBlocks( show: boolean ): ShowHiddenBlocksAction {
		return { type: 'SHOW_HIDDEN_BLOCKS', show };
	},
	previewConditions( conditions: readonly MediaCondition[] ): PreviewConditionsAction {
		return { type: 'PREVIEW_CONDITIONS', conditions };
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
	/** The inspected look's LookKey id ("" for the base): a plain string, so selecting it never re-renders for nothing. */
	getInspectedLook( state: State, key: string ): string {
		return state.inspectedLooks[ key ] ?? '';
	},
	isShowingUnavailableSettings( state: State ): boolean {
		return state.showUnavailableSettings;
	},
	getSidebarView( state: State ): SidebarView | null {
		return state.sidebarView;
	},
	isShowingHiddenBlocks( state: State ): boolean {
		return state.showHiddenBlocks;
	},
	getPreviewConditions( state: State ): readonly MediaCondition[] {
		return state.previewConditions;
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
			case 'SHOW_UNAVAILABLE_SETTINGS':
				return { ...state, showUnavailableSettings: action.show };
			case 'SET_SIDEBAR_VIEW':
				return { ...state, sidebarView: action.view };
			case 'SHOW_HIDDEN_BLOCKS':
				return { ...state, showHiddenBlocks: action.show };
			case 'PREVIEW_CONDITIONS':
				return { ...state, previewConditions: action.conditions };
			case 'INSPECT_LOOK': {
				const { [ action.key ]: _previous, ...others } = state.inspectedLooks;
				return { ...state, inspectedLooks: action.look === '' ? others : { ...others, [ action.key ]: action.look } };
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
