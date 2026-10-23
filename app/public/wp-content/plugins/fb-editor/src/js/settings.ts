/**
 * Boundary for `window.fbEditorSettings` (Fb\Editor\EditorSettings in PHP):
 * unknown JSON in, typed objects out. Anything malformed throws.
 */
import { breakpointListFromJson, type BreakpointList } from './breakpoints/breakpoint';
import { blockDefinitionFromJson, type BlockDefinition } from './core/definition/block-definition';
import { InvalidJson, JsonValue } from './core/json';
import { PresetVariable, PresetVariables } from './core/values/presets';

export class EditorSettings {
	constructor(
		public readonly breakpoints: BreakpointList,
		public readonly presets: PresetVariables,
		public readonly blocks: readonly BlockDefinition[]
	) {}

	block( name: string ): BlockDefinition {
		const block = this.blocks.find( ( candidate ) => candidate.name === name );
		if ( block === undefined ) {
			throw new InvalidJson( `fbEditorSettings.blocks: no definition for "${ name }"` );
		}
		return block;
	}
}

declare global {
	interface Window {
		fbEditorSettings?: unknown;
	}
}

export function parseEditorSettings( raw: unknown ): EditorSettings {
	const json = JsonValue.of( raw, 'fbEditorSettings' );

	return new EditorSettings(
		breakpointListFromJson( json.get( 'breakpoints' ) ),
		new PresetVariables( json.get( 'presets' ).list().map( PresetVariable.fromJson ) ),
		json.get( 'blocks' ).list().map( blockDefinitionFromJson )
	);
}

export const settings: EditorSettings = parseEditorSettings( window.fbEditorSettings );
