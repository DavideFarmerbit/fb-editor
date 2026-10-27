/**
 * Editor UI for FocalPointInput: WordPress's focal point picker on the
 * block's own image (the input's media control: own value, else what it
 * falls back to), with reset. Without an image (or for a video) it says
 * so instead.
 */
import { FocalPointPicker } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { MediaKind, MediaValue } from '../../../../Values/content';
import { FocalPoint } from '../../../../Values/focal-point';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import { useMedia } from '../Media/use-media';
import type { FocalPointInput } from './focal-point-input';

interface FocalPointFieldProps extends ControlFieldProps {
	readonly input: FocalPointInput;
}

const CENTER = new FocalPoint( 0.5, 0.5 );

export function FocalPointField( props: FocalPointFieldProps ): ReactElement {
	const { control, input, values, fallbacks, breakpoints, onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown instanceof FocalPoint ? reading.shown : CENTER;

	// The image lives in a static control: its value is at the base breakpoint.
	const base = breakpoints.base.id;
	const ownMedia = values.get( input.media ).at( base );
	const media = ownMedia ?? fallbacks.resolve( input.media, base, breakpoints )?.value ?? null;
	const image = media instanceof MediaValue && media.kind === MediaKind.Image ? media : null;
	const item = useMedia( image?.id ?? null );

	if ( item === null ) {
		return <p className="fb-control__inactive">{ __( 'Set a background image to pick its focal point.', 'fb-editor' ) }</p>;
	}

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<FocalPointPicker
				__nextHasNoMarginBottom
				label={ control.label }
				hideLabelFromVision
				url={ item.url }
				value={ { x: shown.x, y: shown.y } }
				onChange={ ( point: { x: number; y: number } ) => onChange( new FocalPoint( point.x, point.y ) ) }
			/>
		</Row>
	);
}
