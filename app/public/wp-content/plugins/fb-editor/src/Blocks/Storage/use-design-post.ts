/**
 * One block type's design post (defaults, variants…) in the editor, read
 * and edited through core-data. Edits are entity edits: they join the
 * editor's own save flow and appear in its "Save" panel, like global
 * styles.
 */
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

import type { BlockDefinition } from '../Definition/block-definition';

const KIND = 'postType';

export class DesignPost {
	constructor(
		/** The decoded content, edits included; null when none (yet). */
		public readonly content: unknown,
		/** Whether the user may change it (edit_theme_options). */
		public readonly canEdit: boolean,
		/** Puts new content in as an unsaved edit (creating the post if needed). */
		public readonly save: ( content: unknown ) => void
	) {}
}

/** Post content as core-data holds it: a raw string once edited, {raw} before. */
function rawContent( content: unknown ): string | null {
	if ( typeof content === 'string' ) {
		return content;
	}
	if ( typeof content === 'object' && content !== null && 'raw' in content && typeof content.raw === 'string' ) {
		return content.raw;
	}
	return null;
}

function decode( raw: string | null ): unknown {
	if ( raw === null || raw === '' ) {
		return null;
	}
	try {
		return JSON.parse( raw ) as unknown;
	} catch {
		return null;
	}
}

function recordId( record: unknown ): number | null {
	return typeof record === 'object' && record !== null && 'id' in record && typeof record.id === 'number' ? record.id : null;
}

/**
 * @param postType The post type (from its generated POST_TYPE).
 * @param postSlug The type's post slug for the active theme (from PHP).
 * @param empty    Content of a freshly created post.
 */
export function useDesignPost( definition: BlockDefinition, postType: string, postSlug: string, empty: string ): DesignPost {
	const { id, raw, canEdit } = useSelect(
		( select ) => {
			const core = select( coreStore );
			const records: unknown = core.getEntityRecords( KIND, postType, { slug: postSlug, per_page: 1, status: 'publish' } );
			const first: unknown = Array.isArray( records ) ? records[ 0 ] : null;
			const found = recordId( first );
			const edited: unknown = found === null ? null : core.getEditedEntityRecord( KIND, postType, found );
			return {
				id: found,
				raw: typeof edited === 'object' && edited !== null && 'content' in edited ? rawContent( edited.content ) : null,
				canEdit: core.canUser( 'create', { kind: KIND, name: postType } ) === true,
			};
		},
		[ postType, postSlug ]
	);

	const { editEntityRecord, saveEntityRecord } = useDispatch( coreStore );

	const save = useCallback(
		( content: unknown ): void => {
			const edits = { content: JSON.stringify( content ) };
			if ( id !== null ) {
				void editEntityRecord( KIND, postType, id, edits );
				return;
			}
			// First one for this block type and theme: create the (empty)
			// post now, then put the content in as an unsaved edit.
			void Promise.resolve(
				saveEntityRecord( KIND, postType, { title: definition.name, slug: postSlug, status: 'publish', content: empty } )
			).then( ( record: unknown ) => {
				const created = recordId( record );
				if ( created !== null ) {
					void editEntityRecord( KIND, postType, created, edits );
				}
			} );
		},
		[ id, definition, postType, postSlug, empty, editEntityRecord, saveEntityRecord ]
	);

	return new DesignPost( decode( raw ), canEdit, save );
}
