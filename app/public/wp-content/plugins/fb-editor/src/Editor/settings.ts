/**
 * Boundary for `window.fbEditorSettings` (Fb\Editor\EditorSettings in PHP):
 * unknown JSON in, typed objects out. Anything malformed throws.
 */
import { breakpointListFromJson, type BreakpointList } from '../Breakpoints/breakpoints';
import { BlockDefaultsInfo } from '../Blocks/Defaults/block-defaults-info';
import { blockDefinitionFromJson, type BlockDefinition } from '../Blocks/Definition/block-definition';
import { BlockValues } from '../Blocks/Values/block-values';
import { InvalidJson, JsonValue } from '../Json/json';
import { Preset, Presets } from '../Presets/presets';

export class EditorSettings {
	constructor(
		public readonly breakpoints: BreakpointList,
		public readonly presets: Presets,
		public readonly blocks: readonly BlockDefinition[],
		private readonly defaults: readonly BlockDefaultsInfo[]
	) {}

	defaultsFor( definition: BlockDefinition ): BlockDefaultsInfo {
		const info = this.defaults.find( ( candidate ) => candidate.block === definition.name );
		if ( info === undefined ) {
			throw new InvalidJson( `fbEditorSettings.defaults: none for "${ definition.name }"` );
		}
		return info;
	}

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

	const breakpoints = breakpointListFromJson( json.get( 'breakpoints' ) );
	const blocks = json.get( 'blocks' ).list().map( blockDefinitionFromJson );

	const defaults = json
		.get( 'defaults' )
		.list()
		.map( ( item ) => {
			const name = item.get( 'block' ).string();
			const definition = blocks.find( ( block ) => block.name === name );
			if ( definition === undefined ) {
				throw new InvalidJson( `${ item.path }.block: no definition for "${ name }"` );
			}
			return new BlockDefaultsInfo(
				name,
				item.get( 'postSlug' ).string(),
				BlockValues.fromAttribute( item.get( 'theme' ).decoded(), definition, breakpoints )
			);
		} );

	return new EditorSettings( breakpoints, new Presets( json.get( 'presets' ).list().map( Preset.fromJson ) ), blocks, defaults );
}

export const settings: EditorSettings = parseEditorSettings( window.fbEditorSettings );
