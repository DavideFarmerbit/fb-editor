/**
 * Editor UI for NumberInput, on one line: a number box (showing what
 * applies when unset here) and reset. Out-of-range input is ignored.
 */
import { __experimentalNumberControl as NumberControl } from '@wordpress/components';
import type { ReactElement } from 'react';

import { NumberValue } from '../../../../Values/number';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { NumberInput } from './number-input';

interface NumberBoxProps {
	readonly label: string;
	readonly input: NumberInput;
	readonly shown: NumberValue | null;
	readonly onChange: ( value: NumberValue | null ) => void;
}

/** The bare number box, also used by other fields (grid column count). */
export function NumberBox( { label, input, shown, onChange }: NumberBoxProps ): ReactElement {
	return (
		<NumberControl
			__next40pxDefaultSize
			label={ label }
			hideLabelFromVision
			value={ shown === null ? '' : String( shown.value ) }
			step={ input.step }
			{ ...( input.min === null ? {} : { min: input.min } ) }
			{ ...( input.max === null ? {} : { max: input.max } ) }
			onChange={ ( raw: string | undefined ) => {
				if ( raw === undefined || raw.trim() === '' ) {
					onChange( null );
					return;
				}
				const parsed = Number( raw );
				if ( Number.isFinite( parsed ) ) {
					const value = new NumberValue( parsed );
					if ( input.allows( value ) ) {
						onChange( value );
					}
				}
			} }
		/>
	);
}

interface NumberFieldProps extends ControlFieldProps {
	readonly input: NumberInput;
}

export function NumberField( props: NumberFieldProps ): ReactElement {
	const { control, input, onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown instanceof NumberValue ? reading.shown : null;

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<NumberBox key={ reading.at } label={ control.label } input={ input } shown={ shown } onChange={ onChange } />
		</Row>
	);
}
