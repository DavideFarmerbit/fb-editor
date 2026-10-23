/**
 * Registers the editor half of an fb block. Title, attributes and category
 * mirror what Fb\Blocks\BlockRegistry registers in PHP; rendering is
 * server-side, so `save` only serializes inner blocks.
 */
import { registerBlockType, type BlockEditProps } from '@wordpress/blocks';
import type { ReactElement } from 'react';

import { settings } from '../settings';
import type { BlockDefinition } from '../core/definition/block-definition';

export interface FbAttributes extends Record< string, unknown > {
	/** All control values. Parsed through the block definition, never read raw. */
	fb: unknown;
	className?: string;
}

export interface FbBlockEditProps extends BlockEditProps< FbAttributes > {
	readonly definition: BlockDefinition;
}

export function registerFbBlock(
	name: string,
	edit: ( props: FbBlockEditProps ) => ReactElement,
	save: () => ReactElement | null
): void {
	const definition = settings.block( name );

	registerBlockType< FbAttributes >( name, {
		title: definition.title,
		category: 'design',
		attributes: {
			fb: { type: 'object', default: {} },
		},
		edit: ( props ) => edit( { ...props, definition } ),
		save,
	} );
}
