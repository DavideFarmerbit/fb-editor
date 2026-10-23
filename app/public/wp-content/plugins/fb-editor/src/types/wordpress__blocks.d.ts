/**
 * Adds the overload @wordpress/blocks' types are missing: for blocks
 * registered in PHP, `registerBlockType( name, { edit, save } )` merges the
 * metadata WordPress bootstraps from the server registration (title,
 * category, attributes, supports). Fb\Blocks\BlockRegistry is the single
 * source of that metadata; JS must not repeat it.
 *
 * Verified against WordPress 7.1.3 (processBlockType spreads
 * getBootstrappedBlockType() under the client settings).
 */
import type { BlockConfiguration, BlockType } from '@wordpress/blocks';

declare module '@wordpress/blocks' {
	export function registerBlockType< Attributes extends Record< string, unknown > >(
		name: string,
		settings: Pick< BlockConfiguration< Attributes >, 'edit' | 'save' >
	): BlockType< Attributes > | undefined;
}
