/**
 * Editor UI for ToggleInput: a switch for the value at this breakpoint.
 * Unset here: shows what applies instead (inherited or the type default),
 * and a reset once overridden.
 */
import { Button, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { reset } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { BooleanValue } from '../../../../Values/boolean';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import type { ToggleInput } from './toggle-input';
import './toggle-control.scss';

interface ToggleFieldProps extends ControlFieldProps {
	readonly input: ToggleInput;
}

export function ToggleField( props: ToggleFieldProps ): ReactElement {
	const { onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown;
	const on = shown instanceof BooleanValue && shown.on;

	return (
		<div className="fb-toggle">
			<ToggleControl
				__nextHasNoMarginBottom
				label={ on ? __( 'On', 'fb-editor' ) : __( 'Off', 'fb-editor' ) }
				{ ...( reading.status === null ? {} : { help: reading.status } ) }
				checked={ on }
				onChange={ ( checked: boolean ) => onChange( new BooleanValue( checked ) ) }
			/>
			{ reading.own !== null && (
				<Button size="small" icon={ reset } label={ __( 'Reset to inherited', 'fb-editor' ) } onClick={ () => onChange( null ) } />
			) }
		</div>
	);
}
