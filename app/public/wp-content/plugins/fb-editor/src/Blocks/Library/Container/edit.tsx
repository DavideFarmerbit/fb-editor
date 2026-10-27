/**
 * fb/container editor markup. Mirrors Container::render():
 *
 *   <div class="fb-container">[media][overlay]…inner blocks…</div>
 *
 * Inner blocks are direct children of the root, as on the frontend; the
 * background media and overlay come first, behind them (base CSS). Markup
 * only: names come from Container.php; sidebar and live CSS from the
 * wrapper in register.ts.
 */
import { InnerBlocks, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';
import type { ReactElement } from 'react';

import { useMedia } from '../../Definition/Input/Media/use-media';
import { BlockEditor, type FbBlockEditProps } from '../../register';
import { MediaKind, MediaValue } from '../../../Values/content';
import { BACKGROUND_MEDIA, MEDIA, NAME, OVERLAY, OVERLAY_COLOR, OVERLAY_GRADIENT, TAG } from './Container.generated';

function Edit( { definition, values, variant, defaults, rootProps }: FbBlockEditProps ): ReactElement {
	const { children, ...innerBlocksProps } = useInnerBlocksProps( useBlockProps( rootProps ) );
	const value = definition.value( BACKGROUND_MEDIA, values, variant, defaults );
	const media = value instanceof MediaValue ? value : null;
	const item = useMedia( media?.id ?? null );

	const mediaClass = definition.elementClass( MEDIA );
	let background: ReactElement | null = null;
	if ( media !== null && item !== null ) {
		background =
			media.kind === MediaKind.Video ? (
				<video className={ mediaClass } src={ item.url } autoPlay muted loop playsInline aria-hidden="true" />
			) : (
				<img className={ mediaClass } src={ item.url } alt="" aria-hidden="true" />
			);
	}

	// Same rule as render(): an overlay when something shows through or styles it.
	const overlayIds = [ definition.controlId( OVERLAY_COLOR ), definition.controlId( OVERLAY_GRADIENT ) ];
	const hasOverlay =
		background !== null || [ values, variant, defaults ].some( ( source ) => source !== null && overlayIds.some( ( id ) => source.has( id ) ) );

	return createElement(
		definition.option( TAG, values, variant, defaults ),
		innerBlocksProps,
		background,
		hasOverlay ? <span className={ definition.elementClass( OVERLAY ) } aria-hidden="true" /> : null,
		children
	);
}

export const containerEditor = new BlockEditor( NAME, Edit, () => <InnerBlocks.Content /> );
