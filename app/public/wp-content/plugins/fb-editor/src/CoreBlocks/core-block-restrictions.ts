/**
 * Editor side of CoreBlockRestrictions: the editor registers core blocks
 * from its own bundled metadata (overriding the server's), so the same
 * supports and style variations are stripped here, from the same PHP lists.
 *
 * Must load before core blocks register (they do on domReady).
 */
import { addFilter } from '@wordpress/hooks';

import { AllowedCoreBlock } from './AllowedCoreBlock.generated';
import { StrippedSupport } from './StrippedSupport.generated';

const ALLOWED: ReadonlySet< string > = new Set( Object.values( AllowedCoreBlock ) );
const STRIPPED: readonly string[] = Object.values( StrippedSupport );

/** Block settings as the filter receives them (core's metadata, boundary). */
type BlockSettings = Record< string, unknown >;

function withoutStyling( settings: BlockSettings ): BlockSettings {
	const supports = settings.supports;
	const kept =
		typeof supports === 'object' && supports !== null
			? Object.fromEntries( Object.entries( supports ).filter( ( [ key ] ) => ! STRIPPED.includes( key ) ) )
			: supports;

	return { ...settings, supports: kept, styles: [] };
}

addFilter( 'blocks.registerBlockType', 'fb/core-block-restrictions', ( settings: BlockSettings, name: string ): BlockSettings =>
	ALLOWED.has( name ) ? withoutStyling( settings ) : settings
);
