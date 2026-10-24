/**
 * One shared <style> per key and document (e.g. one defaults sheet per
 * block type in the canvas iframe), reference-counted: every block of a
 * type can ask for it, it exists once, and it goes away with the last user.
 */
import { useEffect } from '@wordpress/element';

class SharedStyle {
	users = 0;

	constructor( public readonly element: HTMLStyleElement ) {}
}

const sheets = new WeakMap< Document, Map< string, SharedStyle > >();

function claim( document: Document, key: string ): SharedStyle {
	let byKey = sheets.get( document );
	if ( byKey === undefined ) {
		byKey = new Map();
		sheets.set( document, byKey );
	}

	let shared = byKey.get( key );
	if ( shared === undefined ) {
		const element = document.createElement( 'style' );
		element.dataset.fb = key;
		document.head.appendChild( element );
		shared = new SharedStyle( element );
		byKey.set( key, shared );
	}

	shared.users++;
	return shared;
}

function release( document: Document, key: string ): void {
	const byKey = sheets.get( document );
	const shared = byKey?.get( key );
	if ( byKey === undefined || shared === undefined ) {
		return;
	}

	shared.users--;
	if ( shared.users <= 0 ) {
		shared.element.remove();
		byKey.delete( key );
	}
}

export function useDocumentStyle( document: Document | null, key: string, css: string ): void {
	useEffect( () => {
		if ( document === null ) {
			return undefined;
		}
		claim( document, key );
		return () => release( document, key );
	}, [ document, key ] );

	useEffect( () => {
		const shared = document === null ? undefined : sheets.get( document )?.get( key );
		if ( shared !== undefined && shared.element.textContent !== css ) {
			shared.element.textContent = css;
		}
	}, [ document, key, css ] );
}
