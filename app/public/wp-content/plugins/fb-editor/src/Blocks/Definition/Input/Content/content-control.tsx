/**
 * Editor UI for ContentInput, on one line: what the part shows (text or
 * symbols), then reset. Empty is kept (an empty box: a line, a dot…); reset
 * removes the value, and nothing is drawn.
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { TextValue } from '../../../../Values/content';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import { ContentInput } from './content-input';

interface ContentFieldProps extends ControlFieldProps {
	readonly input: ContentInput;
}

export function ContentField( props: ContentFieldProps ): ReactElement {
	const { control, input, onChange } = props;
	const reading = readControl( props );

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ control.label }
				hideLabelFromVision
				maxLength={ input.maxLength }
				placeholder={ __( 'Empty box', 'fb-editor' ) }
				value={ reading.shown instanceof TextValue ? reading.shown.text : '' }
				onChange={ ( value: string ) => onChange( ContentInput.clean( value ) ) }
			/>
		</Row>
	);
}
