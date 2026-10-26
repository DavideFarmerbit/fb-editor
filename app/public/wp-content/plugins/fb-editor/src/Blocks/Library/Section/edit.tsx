/**
 * fb/section editor markup. Mirrors Section::render():
 *
 *   <section class="fb-section"><div class="fb-section__inner">…</div></section>
 *
 * Markup only. Names, tags and the fallback tag all come from Section.php;
 * sidebar and live CSS come from the wrapper in register.ts.
 */
import { InnerBlocks, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';
import type { ReactElement } from 'react';

import { BlockEditor, type FbBlockEditProps } from '../../register';
import { INNER, NAME, TAG } from './Section.generated';

function Edit( { definition, values, variant, defaults, rootProps }: FbBlockEditProps ): ReactElement {
	const blockProps = useBlockProps( rootProps );
	const innerBlocksProps = useInnerBlocksProps( { className: definition.elementClass( INNER ) } );

	return createElement( definition.option( TAG, values, variant, defaults ), blockProps, <div { ...innerBlocksProps } /> );
}

export const sectionEditor = new BlockEditor( NAME, Edit, () => <InnerBlocks.Content /> );
