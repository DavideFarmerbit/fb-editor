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

export class EditorSettings {
	constructor( public readonly breakpoints: BreakpointList ) {}
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

export function parseEditorSettings( raw: unknown ): EditorSettings {
	if ( ! isRecord( raw ) || ! Array.isArray( raw.breakpoints ) ) {
		throw new InvalidSettings( 'fbEditorSettings.breakpoints: expected an array' );
	}

	const [ base, ...others ] = raw.breakpoints.map( ( item: unknown, index: number ) =>
		parseBreakpoint( item, `fbEditorSettings.breakpoints[${ index }]` )
	);

	if ( base === undefined || ! base.isBase() ) {
		throw new InvalidSettings( 'fbEditorSettings.breakpoints: first entry must be the base' );
	}

	return new EditorSettings( new BreakpointList( base, others ) );
}

export const settings: EditorSettings = parseEditorSettings( window.fbEditorSettings );
