/**
 * fb/container editor markup. Mirrors Container::render():
 *
 *   <div class="fb-container">…inner blocks…</div>
 *
 * Inner blocks are direct children of the root, as on the frontend. Markup
 * only: names and the fallback tag come from Container.php; sidebar and
 * live CSS come from the wrapper in register.ts.
 */
import { InnerBlocks, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';
import type { ReactElement } from 'react';

import { BlockEditor, type FbBlockEditProps } from '../../register';
import { NAME, TAG } from './Container.generated';

function Edit( { definition, values, variant, defaults, rootProps }: FbBlockEditProps ): ReactElement {
	const innerBlocksProps = useInnerBlocksProps( useBlockProps( rootProps ) );

	return createElement( definition.option( TAG, values, variant, defaults ), innerBlocksProps );
}

export const containerEditor = new BlockEditor( NAME, Edit, () => <InnerBlocks.Content /> );
