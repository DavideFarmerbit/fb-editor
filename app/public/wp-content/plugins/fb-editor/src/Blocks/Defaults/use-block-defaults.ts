/**
 * A block type's defaults in the editor: theme defaults (from settings)
 * with the saved defaults post laid on top, read and edited through
 * core-data. Edits are entity edits: they join the editor's own save flow
 * and appear in its "Save" panel, like global styles.
 */
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useMemo } from '@wordpress/element';

import { settings } from '../../Editor/settings';
import type { BlockDefinition } from '../Definition/block-definition';
import { BlockValues } from '../Values/block-values';
import { POST_TYPE } from './BlockDefaultsPostType.generated';

const KIND = 'postType';

export class BlockDefaults {
	constructor(
		/** Theme + saved: what unset values fall back to. */
		public readonly values: BlockValues,
		/** Saved only: what "Make default" and the defaults sidebar edit. */
		public readonly saved: BlockValues,
		/** The theme's defaults (fb.json), read-only: what saved ones overlay. */
		public readonly theme: BlockValues,
		/** Whether the user may change defaults (edit_theme_options). */
		public readonly canEdit: boolean
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

function parseSaved( raw: string | null ): unknown {
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

export function useBlockDefaults( definition: BlockDefinition ): { defaults: BlockDefaults; save: ( saved: BlockValues ) => void } {
	const info = settings.defaultsFor( definition );

	const { id, content, canEdit } = useSelect(
		( select ) => {
			const core = select( coreStore );
			const records: unknown = core.getEntityRecords( KIND, POST_TYPE, { slug: info.postSlug, per_page: 1, status: 'publish' } );
			const first: unknown = Array.isArray( records ) ? records[ 0 ] : null;
			const found = recordId( first );
			const edited: unknown = found === null ? null : core.getEditedEntityRecord( KIND, POST_TYPE, found );
			return {
				id: found,
				content: typeof edited === 'object' && edited !== null && 'content' in edited ? rawContent( edited.content ) : null,
				canEdit: core.canUser( 'create', { kind: KIND, name: POST_TYPE } ) === true,
			};
		},
		[ info.postSlug ]
	);

	const { editEntityRecord, saveEntityRecord } = useDispatch( coreStore );

	const defaults = useMemo( () => {
		const saved = BlockValues.fromAttribute( parseSaved( content ), definition, settings.breakpoints );
		return new BlockDefaults( info.theme.overlay( saved ), saved, info.theme, canEdit );
	}, [ content, definition, info, canEdit ] );

	const save = useCallback(
		( saved: BlockValues ): void => {
			const edits = { content: JSON.stringify( saved.toAttribute() ) };
			if ( id !== null ) {
				void editEntityRecord( KIND, POST_TYPE, id, edits );
				return;
			}
			// First default for this block type and theme: create the (empty)
			// post now, then put the values in as an unsaved edit.
			void Promise.resolve(
				saveEntityRecord( KIND, POST_TYPE, { title: definition.name, slug: info.postSlug, status: 'publish', content: '{}' } )
			).then( ( record: unknown ) => {
				const created = recordId( record );
				if ( created !== null ) {
					void editEntityRecord( KIND, POST_TYPE, created, edits );
				}
			} );
		},
		[ id, definition, info, editEntityRecord, saveEntityRecord ]
	);

	return { defaults, save };
}
