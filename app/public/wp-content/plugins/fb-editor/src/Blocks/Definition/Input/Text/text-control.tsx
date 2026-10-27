/**
 * Editor UI for TextInput, on one line: a text box and reset.
 */
import { TextControl } from '@wordpress/components';
import type { ReactElement } from 'react';

import { TextValue } from '../../../../Values/content';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { TextInput } from './text-input';

interface TextFieldProps extends ControlFieldProps {
	readonly input: TextInput;
}

export function TextField( props: TextFieldProps ): ReactElement {
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
				value={ reading.shown instanceof TextValue ? reading.shown.text : '' }
				onChange={ ( value: string ) => onChange( value.trim() === '' ? null : new TextValue( value ) ) }
			/>
		</Row>
	);
}
