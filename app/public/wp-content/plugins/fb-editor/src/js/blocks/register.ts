/**
 * Registers the editor half of an fb block. Title, attributes and category
 * mirror what Fb\Blocks\BlockRegistry registers in PHP; rendering is
 * server-side, so `save` only serializes inner blocks.
 */
import { registerBlockType, type BlockEditProps } from '@wordpress/blocks';
import type { ReactElement } from 'react';

import { settings } from '../settings';
import type { BlockType } from './block-type';

export interface FbAttributes extends Record< string, unknown > {
	/** All control values. Parsed through the block definition, never read raw. */
	fb: unknown;
	className?: string;
}

export interface FbBlockEditProps extends BlockEditProps< FbAttributes > {
	readonly blockType: BlockType;
}

export function registerFbBlock(
	name: string,
	edit: ( props: FbBlockEditProps ) => ReactElement,
	save: () => ReactElement | null
): void {
	const blockType = settings.block( name );

	registerBlockType< FbAttributes >( name, {
		title: blockType.title,
		category: 'design',
		attributes: {
			fb: { type: 'object', default: {} },
		},
		edit: ( props ) => edit( { ...props, blockType } ),
		save,
	} );
}
