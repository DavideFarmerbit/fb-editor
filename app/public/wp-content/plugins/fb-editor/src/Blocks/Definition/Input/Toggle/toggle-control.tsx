/**
 * Editor UI for ToggleInput: a switch for the value at this breakpoint.
 * Unset here: shows the inherited state with its source, and a reset once
 * overridden.
 */
import { Button, ToggleControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { reset } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { BooleanValue } from '../../../../Values/boolean';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { Scope } from '../../block-definition';
import type { ToggleInput } from './toggle-input';
import './toggle-control.scss';

interface ToggleFieldProps extends ControlFieldProps {
	readonly input: ToggleInput;
}

export function ToggleField( { control, values, breakpoint, breakpoints, onChange }: ToggleFieldProps ): ReactElement {
	const at = control.scope === Scope.Static ? breakpoints.base.id : breakpoint.id;
	const responsive = values.get( control.id );
	const own = responsive.at( at );
	const resolved = responsive.resolve( at, breakpoints );
	const current = own ?? resolved;
	const on = current instanceof BooleanValue && current.on;
	const source = own === null && resolved !== null ? breakpoints.get( responsive.source( at, breakpoints ) ?? at ) : undefined;

	return (
		<div className="fb-toggle">
			<ToggleControl
				__nextHasNoMarginBottom
				label={ on ? __( 'On', 'fb-editor' ) : __( 'Off', 'fb-editor' ) }
				help={ source === undefined ? undefined : /* translators: %s: breakpoint label */ sprintf( __( 'Inherited from %s', 'fb-editor' ), source.label ) }
				checked={ on }
				onChange={ ( checked: boolean ) => onChange( new BooleanValue( checked ) ) }
			/>
			{ own !== null && (
				<Button size="small" icon={ reset } label={ __( 'Reset to inherited', 'fb-editor' ) } onClick={ () => onChange( null ) } />
			) }
		</div>
	);
}
