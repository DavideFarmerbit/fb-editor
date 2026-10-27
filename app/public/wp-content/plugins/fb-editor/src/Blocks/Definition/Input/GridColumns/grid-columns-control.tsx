/**
 * Editor UI for GridColumnsInput: the mode (a count, or "as many as fit")
 * as two buttons in the control's header line, then one row: the count, or
 * the minimum column width. Switching mode sets that mode's starting value
 * (from PHP) at the inspected breakpoint.
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { columns, stretchFullWidth } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { GridColumnCount, GridColumnFit } from '../../../../Values/grid-columns';
import { CustomLength, Unit, type Length } from '../../../../Values/length';
import { NumberValue } from '../../../../Values/number';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { FrameActions } from '../../../Inspector/frame-actions';
import { Row } from '../../../Inspector/row';
import { LengthRow } from '../Length/length-control';
import type { LengthRules } from '../Length/length-input';
import { NumberBox } from '../Number/number-control';
import { NumberInput } from '../Number/number-input';
import type { GridColumnsInput } from './grid-columns-input';
import { DEFAULT_COUNT, DEFAULT_FIT_MIN_PX } from './GridColumnsInput.generated';

/** The minimum column width: a positive custom length, nothing else. */
const FIT_RULES: LengthRules = {
	allowAuto: false,
	allowNegative: false,
	scale: null,
	allows: ( length: Length ): boolean => length instanceof CustomLength && length.value > 0,
};

interface GridColumnsFieldProps extends ControlFieldProps {
	readonly input: GridColumnsInput;
}

export function GridColumnsField( props: GridColumnsFieldProps ): ReactElement {
	const { control, input, onChange } = props;
	const reading = readControl( props );
	const own = reading.own instanceof GridColumnCount || reading.own instanceof GridColumnFit ? reading.own : null;
	const shown = reading.shown instanceof GridColumnCount || reading.shown instanceof GridColumnFit ? reading.shown : null;
	const fit = shown instanceof GridColumnFit;

	return (
		<>
			<FrameActions>
				<Button
					size="small"
					icon={ columns }
					label={ __( 'Number of columns', 'fb-editor' ) }
					isPressed={ ! fit }
					onClick={ () => {
						if ( fit ) {
							onChange( new GridColumnCount( DEFAULT_COUNT ) );
						}
					} }
				/>
				<Button
					size="small"
					icon={ stretchFullWidth }
					label={ __( 'As many as fit', 'fb-editor' ) }
					isPressed={ fit }
					onClick={ () => {
						if ( ! fit ) {
							onChange( new GridColumnFit( new CustomLength( DEFAULT_FIT_MIN_PX, Unit.Px ) ) );
						}
					} }
				/>
			</FrameActions>
			{ fit ? (
				<LengthRow
					// Remount per breakpoint: local edit state must not leak across.
					key={ reading.at }
					label={ __( 'Minimum column width', 'fb-editor' ) }
					own={ own instanceof GridColumnFit ? own.min : null }
					inherited={ own === null && shown instanceof GridColumnFit ? shown.min : null }
					status={ reading.status }
					rules={ FIT_RULES }
					scale={ [] }
					onChange={ ( length ) => onChange( length instanceof CustomLength ? new GridColumnFit( length ) : null ) }
				/>
			) : (
				<Row status={ reading.status } inherited={ own === null } onReset={ own === null ? null : () => onChange( null ) }>
					<NumberBox
						key={ reading.at }
						label={ control.label }
						input={ new NumberInput( 1, input.maxCount, 1, true ) }
						shown={ shown instanceof GridColumnCount ? new NumberValue( shown.count ) : null }
						onChange={ ( value ) => onChange( value === null ? null : new GridColumnCount( value.value ) ) }
					/>
				</Row>
			) }
		</>
	);
}
