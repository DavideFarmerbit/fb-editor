/**
 * Top-bar "View" menu in place of core's (hidden, see adapter.scss): only
 * what still makes sense next to fb's breakpoints, Show template and
 * Preview in new tab. Core's device choices (Desktop / Tablet / Mobile)
 * would resize the canvas against the breakpoint switcher.
 *
 * Plus "Preview as": the media conditions the page is shown and edited with
 * (dark mode, touch…), like the breakpoint: looks requiring them apply in
 * the canvas only while they're checked, whatever the editing device is,
 * and the block sidebars edit the looks for exactly these conditions. A
 * device can't have both of a pair (can hover / touch, portrait /
 * landscape): checking one unchecks the other.
 */
import { DropdownMenu, Fill, Icon, MenuGroup, MenuItem } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { PostPreviewButton, store as editorStore } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { check, external, layout } from '@wordpress/icons';
import type { ComponentProps, ComponentType, ReactElement, ReactNode } from 'react';

import { observeCanvasDocument, PINNED_ITEMS_SLOT } from '../../Adapter/adapter';
import type { ConditionInfo } from '../../Blocks/Definition/block-definition';
import type { MediaCondition } from '../../Blocks/Looks/look-key';
import { settings } from '../../Editor/settings';
import { store } from '../../Editor/store';

/**
 * PostPreviewButton renders `textContent` as the button's children, but
 * its published type only says string: widened here so the label can carry
 * the "opens elsewhere" icon, as core's own menu item does.
 */
const PreviewButton = PostPreviewButton as ComponentType< Omit< ComponentProps< typeof PostPreviewButton >, 'textContent' > & { readonly textContent: ReactNode } >;

/** Core's rendering modes: the post inside its template, or the post alone. */
const WITH_TEMPLATE = 'template-locked';
const POST_ONLY = 'post-only';

/** Keeps the canvas page's classes in step with the previewed conditions. */
function useCanvasConditions( previewed: readonly MediaCondition[] ): void {
	const [ canvas, setCanvas ] = useState< Document | null >( null );
	useEffect( () => observeCanvasDocument( setCanvas ), [] );

	useEffect( () => {
		const root = canvas?.documentElement;
		if ( root === undefined ) {
			return;
		}
		for ( const info of settings.conditions ) {
			root.classList.toggle( info.previewClass, previewed.includes( info.id ) );
		}
	}, [ canvas, previewed ] );
}

export default function ViewOptions(): ReactElement {
	const previewed = useSelect( ( select ) => select( store ).getPreviewConditions(), [] );
	const { previewConditions } = useDispatch( store );
	useCanvasConditions( previewed );

	const toggleCondition = ( info: ConditionInfo ): void => {
		previewConditions(
			previewed.includes( info.id )
				? previewed.filter( ( condition ) => condition !== info.id )
				: [ ...previewed.filter( ( condition ) => condition !== info.excludes ), info.id ]
		);
	};

	const { showsTemplate, canShowTemplate, isViewable } = useSelect( ( select ) => {
		const editor = select( editorStore );
		const postType = editor.getCurrentPostType();
		return {
			showsTemplate: editor.getRenderingMode() !== POST_ONLY,
			// Templates themselves have no template around them.
			canShowTemplate: postType !== 'wp_template' && editor.getCurrentTemplateId() !== null,
			isViewable: select( coreStore ).getPostType( postType )?.viewable ?? false,
		};
	}, [] );
	const { setRenderingMode } = useDispatch( editorStore );

	return (
		<Fill name={ PINNED_ITEMS_SLOT }>
			<DropdownMenu
				icon={ layout }
				// Like the breakpoint, the previewed conditions decide which
				// looks the sidebars edit: never out of sight.
				label={
					previewed.length === 0
						? __( 'View', 'fb-editor' )
						: /* translators: %s: media conditions, e.g. "Dark mode + Touch" */ sprintf(
								__( 'View · previewed as %s', 'fb-editor' ),
								settings.conditions
									.filter( ( info ) => previewed.includes( info.id ) )
									.map( ( info ) => info.label )
									.join( ' + ' )
						  )
				}
				toggleProps={ { size: 'compact', className: previewed.length === 0 ? 'fb-view' : 'fb-view has-conditions' } }
				popoverProps={ { placement: 'bottom-end' } }
			>
				{ ( { onClose } ) => (
					<>
						{ canShowTemplate && (
							<MenuGroup>
								<MenuItem
									icon={ showsTemplate ? check : null }
									isSelected={ showsTemplate }
									role="menuitemcheckbox"
									onClick={ () => setRenderingMode( showsTemplate ? POST_ONLY : WITH_TEMPLATE ) }
								>
									{ __( 'Show template', 'fb-editor' ) }
								</MenuItem>
							</MenuGroup>
						) }
						{ isViewable && (
							<MenuGroup>
								<PreviewButton
									className="components-button components-menu-item__button"
									role="menuitem"
									// MenuItem's layout: label, then the icon on the right.
									textContent={
										<>
											<span className="components-menu-item__item">{ __( 'Preview in new tab', 'fb-editor' ) }</span>
											<Icon icon={ external } />
										</>
									}
									forceIsAutosaveable={ false }
									onPreview={ onClose }
								/>
							</MenuGroup>
						) }
						<MenuGroup label={ __( 'Preview as', 'fb-editor' ) }>
							{ settings.conditions.map( ( info ) => {
								const on = previewed.includes( info.id );
								return (
									<MenuItem key={ info.id } icon={ on ? check : null } isSelected={ on } role="menuitemcheckbox" onClick={ () => toggleCondition( info ) }>
										{ info.label }
									</MenuItem>
								);
							} ) }
						</MenuGroup>
					</>
				) }
			</DropdownMenu>
		</Fill>
	);
}
