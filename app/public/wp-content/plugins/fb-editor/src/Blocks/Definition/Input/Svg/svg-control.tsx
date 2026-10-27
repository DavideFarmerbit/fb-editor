/**
 * Editor UI for SvgInput: one line with a small preview (sanitized), a
 * "Load SVG file…" button and reset; then the code box, where the file's
 * code lands and SVG can also be pasted. Code that isn't a usable SVG is
 * flagged and not stored.
 */
import { TextareaControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { SvgValue } from '../../../../Values/content';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import { sanitizeSvg } from './svg';
import { SvgFileButton } from './svg-file-button';
import type { SvgInput } from './svg-input';
import './svg-control.scss';

interface SvgFieldProps extends ControlFieldProps {
	readonly input: SvgInput;
}

export function SvgField( props: SvgFieldProps ): ReactElement {
	const { control, onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown instanceof SvgValue ? reading.shown.markup : '';
	const [ draft, setDraft ] = useState< string | null >( null );
	const [ fileError, setFileError ] = useState< string | null >( null );
	const preview = sanitizeSvg( draft ?? shown );
	const invalid = draft !== null && draft.trim() !== '' && preview === null;

	const accept = ( value: string ): void => {
		setDraft( value );
		if ( value.trim() === '' ) {
			onChange( null );
		} else if ( sanitizeSvg( value ) !== null ) {
			onChange( new SvgValue( value ) );
		}
	};

	let help: string | undefined;
	if ( fileError !== null ) {
		help = fileError;
	} else if ( invalid ) {
		help = __( 'Not a usable SVG.', 'fb-editor' );
	}

	return (
		<div className="fb-svg">
			<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
				<div className="fb-svg__line">
					<SvgFileButton
						onLoad={ ( markup ) => {
							setFileError( null );
							accept( markup );
						} }
						onError={ setFileError }
					/>
					{ preview !== null && <div className="fb-svg__preview" dangerouslySetInnerHTML={ { __html: preview } } /> }
				</div>
			</Row>
			<TextareaControl
				__nextHasNoMarginBottom
				label={ control.label }
				hideLabelFromVision
				className="fb-svg__code"
				placeholder={ __( 'Paste SVG code', 'fb-editor' ) }
				rows={ 3 }
				value={ draft ?? shown }
				{ ...( help === undefined ? {} : { help } ) }
				onChange={ ( value: string ) => {
					setFileError( null );
					accept( value );
				} }
			/>
		</div>
	);
}
