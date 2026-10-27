/**
 * Top-bar "View" menu in place of core's (hidden, see adapter.scss): only
 * what still makes sense next to fb's breakpoints, Show template and
 * Preview in new tab. Core's device choices (Desktop / Tablet / Mobile)
 * would resize the canvas against the breakpoint switcher.
 */
import { DropdownMenu, Fill, Icon, MenuGroup, MenuItem } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { PostPreviewButton, store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { check, external, layout } from '@wordpress/icons';
import type { ComponentProps, ComponentType, ReactElement, ReactNode } from 'react';

import { PINNED_ITEMS_SLOT } from '../../Adapter/adapter';

/**
 * PostPreviewButton renders `textContent` as the button's children, but
 * its published type only says string: widened here so the label can carry
 * the "opens elsewhere" icon, as core's own menu item does.
 */
const PreviewButton = PostPreviewButton as ComponentType< Omit< ComponentProps< typeof PostPreviewButton >, 'textContent' > & { readonly textContent: ReactNode } >;

/** Core's rendering modes: the post inside its template, or the post alone. */
const WITH_TEMPLATE = 'template-locked';
const POST_ONLY = 'post-only';

export default function ViewOptions(): ReactElement | null {
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

	if ( ! canShowTemplate && ! isViewable ) {
		return null;
	}

	return (
		<Fill name={ PINNED_ITEMS_SLOT }>
			<DropdownMenu
				icon={ layout }
				label={ __( 'View', 'fb-editor' ) }
				toggleProps={ { size: 'compact' } }
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
					</>
				) }
			</DropdownMenu>
		</Fill>
	);
}
