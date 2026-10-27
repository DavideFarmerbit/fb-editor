/**
 * Editor UI for AspectRatioInput, on one line: width : height, then reset.
 * Editing one side keeps the other (16 by default for width, 9 for height
 * when nothing applies yet).
 */
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { AspectRatio } from '../../../../Values/aspect-ratio';
import { NumberValue } from '../../../../Values/number';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import { NumberBox } from '../Number/number-control';
import { NumberInput } from '../Number/number-input';
import type { AspectRatioInput } from './aspect-ratio-input';
import './aspect-ratio-control.scss';

const SIDE = new NumberInput( 0.01, 1000, 1, false );

interface AspectRatioFieldProps extends ControlFieldProps {
	readonly input: AspectRatioInput;
}

export function AspectRatioField( props: AspectRatioFieldProps ): ReactElement {
	const { onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown instanceof AspectRatio ? reading.shown : null;
	const set = ( width: number, height: number ): void => onChange( new AspectRatio( width, height ) );

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<div className="fb-aspect-ratio" key={ reading.at }>
				<NumberBox
					label={ __( 'Width', 'fb-editor' ) }
					input={ SIDE }
					shown={ shown === null ? null : new NumberValue( shown.width ) }
					onChange={ ( value ) => value !== null && set( value.value, shown?.height ?? 9 ) }
				/>
				<span className="fb-aspect-ratio__separator">:</span>
				<NumberBox
					label={ __( 'Height', 'fb-editor' ) }
					input={ SIDE }
					shown={ shown === null ? null : new NumberValue( shown.height ) }
					onChange={ ( value ) => value !== null && set( shown?.width ?? 16, value.value ) }
				/>
			</div>
		</Row>
	);
}
