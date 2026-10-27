/**
 * "Load SVG file…": picks a .svg from the user's computer and hands over
 * its code (or why it couldn't).
 */
import { Button, FormFileUpload } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { chosenFile, readSvgFile, SvgFileError } from './svg-file';

interface SvgFileButtonProps {
	readonly onLoad: ( markup: string ) => void;
	readonly onError: ( message: string ) => void;
}

export function SvgFileButton( { onLoad, onError }: SvgFileButtonProps ): ReactElement {
	return (
		<FormFileUpload
			accept="image/svg+xml,.svg"
			onChange={ ( event ) => {
				const file = chosenFile( event );
				if ( file === null ) {
					return;
				}
				readSvgFile( file ).then( onLoad, ( error: unknown ) => onError( error instanceof SvgFileError ? error.message : String( error ) ) );
			} }
			render={ ( { openFileDialog } ) => (
				<Button __next40pxDefaultSize variant="secondary" onClick={ openFileDialog }>
					{ __( 'Load SVG file…', 'fb-editor' ) }
				</Button>
			) }
		/>
	);
}
