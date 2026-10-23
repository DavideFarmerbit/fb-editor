/**
 * Boundary for `window.fbEditorSettings` (Fb\Editor\EditorSettings in PHP):
 * unknown JSON in, typed objects out. Anything malformed throws.
 */
import {
	Breakpoint,
	BreakpointIcon,
	BreakpointList,
	type BreakpointId,
} from './breakpoints/breakpoint';
import { BlockType } from './blocks/block-type';

export class EditorSettings {
	constructor(
		public readonly breakpoints: BreakpointList,
		public readonly blocks: readonly BlockType[]
	) {}

	block( name: string ): BlockType {
		const block = this.blocks.find( ( candidate ) => candidate.name === name );
		if ( block === undefined ) {
			throw new InvalidSettings( `fbEditorSettings.blocks: no definition for "${ name }"` );
		}
		return block;
	}
}

class InvalidSettings extends Error {}

declare global {
	interface Window {
		fbEditorSettings?: unknown;
	}
}

function isRecord( value: unknown ): value is Record< string, unknown > {
	return typeof value === 'object' && value !== null && ! Array.isArray( value );
}

function readString( record: Record< string, unknown >, key: string, path: string ): string {
	const value = record[ key ];
	if ( typeof value !== 'string' ) {
		throw new InvalidSettings( `${ path }.${ key }: expected a string` );
	}
	return value;
}

function readNullableInt( record: Record< string, unknown >, key: string, path: string ): number | null {
	const value = record[ key ] ?? null;
	if ( value !== null && ! Number.isInteger( value ) ) {
		throw new InvalidSettings( `${ path }.${ key }: expected an integer or null` );
	}
	return value as number | null;
}

function parseIcon( value: unknown ): BreakpointIcon | null {
	return Object.values( BreakpointIcon ).find( ( icon ) => icon === value ) ?? null;
}

function parseBreakpoint( value: unknown, path: string ): Breakpoint {
	if ( ! isRecord( value ) ) {
		throw new InvalidSettings( `${ path }: expected an object` );
	}
	return new Breakpoint(
		readString( value, 'id', path ) as BreakpointId,
		readString( value, 'label', path ),
		parseIcon( value.icon ),
		readNullableInt( value, 'maxWidth', path ),
		readNullableInt( value, 'previewWidth', path )
	);
}

function parseBlockType( value: unknown, path: string ): BlockType {
	if ( ! isRecord( value ) ) {
		throw new InvalidSettings( `${ path }: expected an object` );
	}
	const elements = value.elements;
	if ( ! Array.isArray( elements ) || ! elements.every( ( element ) => typeof element === 'string' ) ) {
		throw new InvalidSettings( `${ path }.elements: expected a list of strings` );
	}
	return new BlockType(
		readString( value, 'name', path ),
		readString( value, 'title', path ),
		readString( value, 'className', path ),
		elements
	);
}

export function parseEditorSettings( raw: unknown ): EditorSettings {
	if ( ! isRecord( raw ) || ! Array.isArray( raw.breakpoints ) ) {
		throw new InvalidSettings( 'fbEditorSettings.breakpoints: expected an array' );
	}
	if ( ! Array.isArray( raw.blocks ) ) {
		throw new InvalidSettings( 'fbEditorSettings.blocks: expected an array' );
	}
	const blocks = raw.blocks.map( ( item: unknown, index: number ) =>
		parseBlockType( item, `fbEditorSettings.blocks[${ index }]` )
	);

	const [ base, ...others ] = raw.breakpoints.map( ( item: unknown, index: number ) =>
		parseBreakpoint( item, `fbEditorSettings.breakpoints[${ index }]` )
	);

	if ( base === undefined || ! base.isBase() ) {
		throw new InvalidSettings( 'fbEditorSettings.breakpoints: first entry must be the base' );
	}

	return new EditorSettings( new BreakpointList( base, others ), blocks );
}

export const settings: EditorSettings = parseEditorSettings( window.fbEditorSettings );
