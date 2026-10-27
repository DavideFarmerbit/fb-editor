/**
 * Editor side of CoreBlockRestrictions: the editor registers core blocks
 * from its own bundled metadata (overriding the server's), so the same
 * supports and style variations are stripped (and the same blocks freed
 * from their parent) here, from the same PHP lists.
 *
 * Must load before core blocks register (they do on domReady).
 */
import { addFilter } from '@wordpress/hooks';

import { AllowedCoreBlock } from './AllowedCoreBlock.generated';
import { FreedCoreBlock } from './FreedCoreBlock.generated';
import { StrippedSupport } from './StrippedSupport.generated';

const ALLOWED: ReadonlySet< string > = new Set( Object.values( AllowedCoreBlock ) );
const STRIPPED: readonly string[] = Object.values( StrippedSupport );
const FREED: ReadonlySet< string > = new Set( Object.values( FreedCoreBlock ) );

/** Block settings as the filter receives them (core's metadata, boundary). */
type BlockSettings = Record< string, unknown >;

function restricted( settings: BlockSettings, name: string ): BlockSettings {
	const supports = settings.supports;
	const kept =
		typeof supports === 'object' && supports !== null
			? Object.fromEntries( Object.entries( supports ).filter( ( [ key ] ) => ! STRIPPED.includes( key ) ) )
			: supports;

	const restrictedSettings: BlockSettings = { ...settings, supports: kept, styles: [] };
	if ( FREED.has( name ) ) {
		delete restrictedSettings.parent;
	}
	return restrictedSettings;
}

addFilter( 'blocks.registerBlockType', 'fb/core-block-restrictions', ( settings: BlockSettings, name: string ): BlockSettings =>
	ALLOWED.has( name ) ? restricted( settings, name ) : settings
);
