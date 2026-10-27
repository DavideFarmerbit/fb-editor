/**
 * Boundary for `window.fbEditorSettings` (Fb\Editor\EditorSettings in PHP):
 * unknown JSON in, typed objects out. Anything malformed throws.
 */
import { breakpointListFromJson, type BreakpointList } from '../Breakpoints/breakpoints';
import { BlockDesignInfo } from '../Blocks/Storage/block-design-info';
import { blockDefinitionFromJson, type BlockDefinition } from '../Blocks/Definition/block-definition';
import { StatefulValues } from '../Blocks/Values/stateful-values';
import { VariantSet } from '../Blocks/Variants/variant-set';
import { InvalidJson, JsonValue } from '../Json/json';
import { Preset, Presets } from '../Presets/presets';

export class EditorSettings {
	constructor(
		public readonly breakpoints: BreakpointList,
		public readonly presets: Presets,
		public readonly blocks: readonly BlockDefinition[],
		private readonly designs: readonly BlockDesignInfo[]
	) {}

	designFor( definition: BlockDefinition ): BlockDesignInfo {
		const info = this.designs.find( ( candidate ) => candidate.block === definition.name );
		if ( info === undefined ) {
			throw new InvalidJson( `fbEditorSettings.designs: none for "${ definition.name }"` );
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

	const designs = json
		.get( 'designs' )
		.list()
		.map( ( item ) => {
			const name = item.get( 'block' ).string();
			const definition = blocks.find( ( block ) => block.name === name );
			if ( definition === undefined ) {
				throw new InvalidJson( `${ item.path }.block: no definition for "${ name }"` );
			}
			return new BlockDesignInfo(
				name,
				item.get( 'postSlug' ).string(),
				StatefulValues.fromJson( item.get( 'themeDefaults' ).decoded(), definition, breakpoints, false ),
				VariantSet.fromJson( item.get( 'themeVariants' ).decoded(), definition, breakpoints )
			);
		} );

	return new EditorSettings( breakpoints, new Presets( json.get( 'presets' ).list().map( Preset.fromJson ) ), blocks, designs );
}

export const settings: EditorSettings = parseEditorSettings( window.fbEditorSettings );
