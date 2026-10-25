/**
 * Editor UI for SelectInput, on one line. Unset here: the first option says
 * what applies instead (inherited, the default, or the input's fallback),
 * and choosing it clears the value.
 */
import { SelectControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { KeywordValue } from '../../../../Values/keyword';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { SelectInput } from './select-input';

interface SelectFieldProps extends ControlFieldProps {
	readonly input: SelectInput;
}

export function SelectField( props: SelectFieldProps ): ReactElement {
	const { control, input, onChange } = props;
	const reading = readControl( props );
	const inherited = reading.inherited instanceof KeywordValue ? reading.inherited.keyword : null;

	const labelOf = ( value: string ): string => input.options.find( ( option ) => option.value === value )?.label ?? value;

	let unsetLabel: string = __( 'Not set', 'fb-editor' );
	if ( inherited !== null ) {
		/* translators: %s: inherited option label */
		unsetLabel = sprintf( __( 'Inherit (%s)', 'fb-editor' ), labelOf( inherited ) );
	} else if ( input.fallback !== null ) {
		/* translators: %s: fallback option label */
		unsetLabel = sprintf( __( 'Default (%s)', 'fb-editor' ), labelOf( input.fallback ) );
	}

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ control.label }
				hideLabelFromVision
				value={ reading.own instanceof KeywordValue ? reading.own.keyword : '' }
				options={ [
					{ value: '', label: unsetLabel },
					...input.options.map( ( option ) => ( { value: option.value, label: option.label } ) ),
				] }
				onChange={ ( value: string ) => onChange( value === '' ? null : new KeywordValue( value ) ) }
			/>
		</Row>
	);
}
