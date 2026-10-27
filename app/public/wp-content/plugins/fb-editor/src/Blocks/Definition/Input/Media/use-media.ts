/**
 * A media library item as core-data has it: its URL and alt text, for
 * previews. Null while loading or when it doesn't exist (anymore).
 */
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

export class MediaItem {
	constructor(
		public readonly url: string,
		public readonly alt: string,
		/** A small rendition for thumbnails (images), else the URL. */
		public readonly thumbnail: string
	) {}
}

function stringAt( record: unknown, ...path: string[] ): string | null {
	let current: unknown = record;
	for ( const key of path ) {
		current = typeof current === 'object' && current !== null && key in current ? ( current as Record< string, unknown > )[ key ] : null;
	}
	return typeof current === 'string' ? current : null;
}

export function useMedia( id: number | null ): MediaItem | null {
	const record: unknown = useSelect( ( select ) => ( id === null ? null : select( coreStore ).getMedia( id, { context: 'view' } ) ), [ id ] );
	const url = stringAt( record, 'source_url' );
	if ( url === null ) {
		return null;
	}
	return new MediaItem( url, stringAt( record, 'alt_text' ) ?? '', stringAt( record, 'media_details', 'sizes', 'thumbnail', 'source_url' ) ?? url );
}
