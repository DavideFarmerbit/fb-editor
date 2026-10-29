/**
 * Blocks as action targets, through the block editor store (public API):
 * finding the block with an anchor, and giving a picked block one.
 */
import { select, dispatch } from '@wordpress/data';

import { ATTRIBUTE } from '../../Blocks/BlockRegistry.generated';
import { settings } from '../../Editor/settings';
import { PATTERN } from '../BlockAnchor.generated';

/** The block editor store, by name: its package ships no store types. */
const BLOCK_EDITOR = 'core/block-editor';

interface BlockEditorSelectors {
	getClientIdsWithDescendants(): string[];
	getBlockAttributes( clientId: string ): Readonly< Record< string, unknown > > | null;
	getBlockName( clientId: string ): string | null;
}

interface BlockEditorActions {
	updateBlockAttributes( clientId: string, attributes: Record< string, unknown > ): void;
}

const editor = (): BlockEditorSelectors => select( BLOCK_EDITOR ) as unknown as BlockEditorSelectors;

/** A block's raw `fb` attribute, as an object. */
function rawOf( clientId: string ): Record< string, unknown > {
	const raw = editor().getBlockAttributes( clientId )?.[ ATTRIBUTE ];
	return typeof raw === 'object' && raw !== null && ! Array.isArray( raw ) ? ( raw as Record< string, unknown > ) : {};
}

export function anchorOf( clientId: string ): string | null {
	const anchor = rawOf( clientId ).anchor;
	return typeof anchor === 'string' && PATTERN.test( anchor ) ? anchor : null;
}

/** Every block with this anchor (several only after a copy: see remap). */
export function blocksWithAnchor( anchor: string ): string[] {
	return editor()
		.getClientIdsWithDescendants()
		.filter( ( clientId ) => anchorOf( clientId ) === anchor );
}

/** The block's type name ("Container", "Button"…), for a target chip. */
export function blockTitle( clientId: string ): string {
	const name = editor().getBlockName( clientId );
	return settings.blocks.find( ( definition ) => definition.name === name )?.title ?? name ?? '';
}

/** Whether fb manages the block (only those can be targets: they render the anchor class). */
export function isManaged( clientId: string ): boolean {
	const name = editor().getBlockName( clientId );
	return settings.blocks.some( ( definition ) => definition.name === name );
}

const ALPHABET = 'abcdefghijklmnopqrstuvwxyz0123456789';

/** A new anchor no block of the page has (6 of [a-z0-9]: BlockAnchor::PATTERN). */
export function newAnchor(): string {
	const taken = new Set( editor().getClientIdsWithDescendants().map( anchorOf ) );
	let anchor: string;
	do {
		anchor = Array.from( crypto.getRandomValues( new Uint8Array( 6 ) ), ( byte ) => ALPHABET[ byte % ALPHABET.length ] ).join( '' );
	} while ( taken.has( anchor ) );
	return anchor;
}

/** Sets a block's anchor, keeping the rest of its `fb` attribute as stored. */
export function setAnchor( clientId: string, anchor: string ): void {
	( dispatch( BLOCK_EDITOR ) as unknown as BlockEditorActions ).updateBlockAttributes( clientId, { [ ATTRIBUTE ]: { ...rawOf( clientId ), anchor } } );
}

/** The block's anchor, given one first if it has none. */
export function ensureAnchor( clientId: string ): string {
	const existing = anchorOf( clientId );
	if ( existing !== null ) {
		return existing;
	}
	const anchor = newAnchor();
	setAnchor( clientId, anchor );
	return anchor;
}
