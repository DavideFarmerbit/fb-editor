/**
 * The post title, editable from the header's left toolbar. The canvas no
 * longer shows it (CanvasStyles: the template decides where the title
 * goes), and core's other ways in are buried (Rename in the Page panel's
 * menu). Same store, same entity, same unsaved change as the canvas title:
 * the same field, moved.
 *
 * Not for templates and template parts: their header name isn't a content
 * title, it's changed from their own panel.
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { createPortal, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { observeHeaderToolbar } from '../../Adapter/adapter';
import './post-title-field.scss';

const EXCLUDED_POST_TYPES: readonly string[] = [ 'wp_template', 'wp_template_part' ];

export default function PostTitleField(): ReactElement | null {
	const [ mount, setMount ] = useState< HTMLElement | null >( null );
	useEffect( () => observeHeaderToolbar( 'fb-post-title', setMount ), [] );

	// Plain values, not one object: useSelect compares results by identity.
	const title = useSelect( ( select ) => String( select( editorStore ).getEditedPostAttribute( 'title' ) ?? '' ), [] );
	const postType = useSelect( ( select ) => select( editorStore ).getCurrentPostType(), [] );
	// While the post loads its attributes read empty: writing then would
	// save an empty title over the real one.
	const isReady = useSelect( ( select ) => select( editorStore ).getCurrentPostId() !== null, [] );
	const { editPost } = useDispatch( editorStore );

	if ( mount === null || ! isReady || EXCLUDED_POST_TYPES.includes( postType ) ) {
		return null;
	}

	return createPortal(
		<input
			type="text"
			className="fb-post-title__input"
			value={ title }
			placeholder={ __( 'Add title', 'fb-editor' ) }
			aria-label={ __( 'Title', 'fb-editor' ) }
			onChange={ ( event ) => editPost( { title: event.target.value } ) }
			// No form to submit up here: Enter just leaves the field.
			onKeyDown={ ( event ) => {
				if ( event.key === 'Enter' ) {
					event.preventDefault();
					event.currentTarget.blur();
				}
			} }
		/>,
		mount
	);
}
