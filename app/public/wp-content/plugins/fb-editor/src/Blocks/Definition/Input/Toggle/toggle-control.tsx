/**
 * Editor UI for ToggleInput, on one line: the switch (showing what applies
 * when unset here) and reset.
 */
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { BooleanValue } from '../../../../Values/boolean';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { ToggleInput } from './toggle-input';

interface ToggleFieldProps extends ControlFieldProps {
	readonly input: ToggleInput;
}

export function ToggleField( props: ToggleFieldProps ): ReactElement {
	const { onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown;
	const on = shown instanceof BooleanValue && shown.on;

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ on ? __( 'On', 'fb-editor' ) : __( 'Off', 'fb-editor' ) }
				checked={ on }
				onChange={ ( checked: boolean ) => onChange( new BooleanValue( checked ) ) }
			/>
		</Row>
	);
}
