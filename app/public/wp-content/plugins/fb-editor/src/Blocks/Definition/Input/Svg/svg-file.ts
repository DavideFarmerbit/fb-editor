/**
 * Reads an SVG file chosen from the user's computer into its code (it is
 * not uploaded anywhere). Only checks that it really is SVG: sanitizing is
 * svg.ts for previews and PHP for real.
 */
import { __ } from '@wordpress/i18n';

export class SvgFileError extends Error {}

export function readSvgFile( file: File ): Promise< string > {
	if ( file.type !== 'image/svg+xml' && ! /\.svg$/i.test( file.name ) ) {
		return Promise.reject( new SvgFileError( __( 'The selected file is not an SVG.', 'fb-editor' ) ) );
	}

	return file.text().then(
		( text ) => {
			const markup = text.trim();
			if ( ! /<svg[\s>]/i.test( markup ) ) {
				throw new SvgFileError( __( 'The file contains no SVG markup.', 'fb-editor' ) );
			}
			return markup;
		},
		() => {
			throw new SvgFileError( __( 'The file could not be read.', 'fb-editor' ) );
		}
	);
}

/** The first file of a file input's change event, if any. */
export function chosenFile( event: { currentTarget: HTMLInputElement } ): File | null {
	return event.currentTarget.files?.[ 0 ] ?? null;
}
