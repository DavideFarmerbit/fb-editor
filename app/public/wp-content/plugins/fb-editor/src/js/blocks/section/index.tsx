/**
 * fb/section editor markup. Mirrors Fb\Blocks\Library\Section\Section::render():
 *
 *   <section class="fb-section"><div class="fb-section__inner">…</div></section>
 *
 * Markup only: the controls panel is generated from the PHP definition.
 */
import { InnerBlocks, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import type { ReactElement } from 'react';

import { registerFbBlock, type FbBlockEditProps } from '../register';

const TAGS = [ 'section', 'div', 'header', 'footer', 'main', 'article', 'aside' ] as const;
type ContainerTag = ( typeof TAGS )[ number ];

function readTag( fb: unknown ): ContainerTag {
	if ( typeof fb === 'object' && fb !== null && 'tag' in fb ) {
		return TAGS.find( ( tag ) => tag === fb.tag ) ?? 'section';
	}
	return 'section';
}

function Edit( { attributes, blockType }: FbBlockEditProps ): ReactElement {
	const Tag = readTag( attributes.fb );
	const blockProps = useBlockProps( { className: blockType.className } );
	const innerBlocksProps = useInnerBlocksProps( { className: blockType.elementClass( 'inner' ) } );

	return (
		<Tag { ...blockProps }>
			<div { ...innerBlocksProps } />
		</Tag>
	);
}

registerFbBlock( 'fb/section', Edit, () => <InnerBlocks.Content /> );
