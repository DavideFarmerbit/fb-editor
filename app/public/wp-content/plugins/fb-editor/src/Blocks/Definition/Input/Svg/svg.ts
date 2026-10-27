/**
 * Mirrors SvgSanitizer for the editor's preview: the same allowlists (from
 * PHP), applied with the browser's own parser. Pasted code is only ever
 * shown through this; PHP sanitizes again whenever it renders.
 */
import { ATTRIBUTES, ELEMENTS, MAX_LENGTH } from './SvgSanitizer.generated';

const elements = ELEMENTS.split( ',' );
const attributes = ATTRIBUTES.split( ',' );
const LOCAL_REFERENCE = /^#[A-Za-z_][\w.-]*$/;
const LOCAL_URL = /^url\(\s*#[A-Za-z_][\w.-]*\s*\)$/i;

function keeps( attribute: Attr ): boolean {
	const name = attribute.name.toLowerCase();
	const value = attribute.value.trim();
	if ( ! attributes.includes( name ) ) {
		return false;
	}
	if ( name === 'href' || name === 'xlink:href' ) {
		return LOCAL_REFERENCE.test( value );
	}
	if ( value.toLowerCase().includes( 'url(' ) ) {
		return LOCAL_URL.test( value );
	}
	return ! value.toLowerCase().includes( 'javascript:' );
}

function clean( element: Element ): void {
	for ( const attribute of Array.from( element.attributes ) ) {
		if ( ! keeps( attribute ) ) {
			element.removeAttribute( attribute.name );
		}
	}
	for ( const child of Array.from( element.childNodes ) ) {
		if ( child instanceof Element ) {
			if ( elements.includes( child.localName.toLowerCase() ) ) {
				clean( child );
			} else {
				child.remove();
			}
		} else if ( child.nodeType !== Node.TEXT_NODE ) {
			child.remove();
		}
	}
}

/** Safe markup to show, or null when it isn't a usable SVG. */
export function sanitizeSvg( raw: string ): string | null {
	const trimmed = raw.trim();
	if ( trimmed === '' || trimmed.length > MAX_LENGTH || /<!DOCTYPE|<!ENTITY/i.test( trimmed ) ) {
		return null;
	}
	const document = new DOMParser().parseFromString( trimmed, 'image/svg+xml' );
	const root = document.documentElement;
	if ( root.getElementsByTagName( 'parsererror' ).length > 0 || root.localName.toLowerCase() !== 'svg' ) {
		return null;
	}
	clean( root );
	return new XMLSerializer().serializeToString( root );
}
