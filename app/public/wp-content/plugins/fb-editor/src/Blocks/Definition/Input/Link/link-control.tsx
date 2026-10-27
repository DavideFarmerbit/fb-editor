/**
 * Editor UI for LinkInput: the URL on one line (with reset); "new tab" and
 * "nofollow" as toggle buttons in the control's header line.
 */
import { Button, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { external, shield } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { LinkValue } from '../../../../Values/content';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { FrameActions } from '../../../Inspector/frame-actions';
import { Row } from '../../../Inspector/row';
import { isAllowedUrl, type LinkInput } from './link-input';

interface LinkFieldProps extends ControlFieldProps {
	readonly input: LinkInput;
}

export function LinkField( props: LinkFieldProps ): ReactElement {
	const { control, onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown instanceof LinkValue ? reading.shown : null;
	const [ draft, setDraft ] = useState< string | null >( null );
	const url = draft ?? shown?.url ?? '';

	return (
		<>
			<FrameActions>
				<Button
					size="small"
					icon={ external }
					label={ __( 'Open in a new tab', 'fb-editor' ) }
					isPressed={ shown?.newTab === true }
					disabled={ shown === null }
					accessibleWhenDisabled
					onClick={ () => shown !== null && onChange( new LinkValue( shown.url, ! shown.newTab, shown.nofollow ) ) }
				/>
				<Button
					size="small"
					icon={ shield }
					label={ __( 'Ask search engines not to follow (nofollow)', 'fb-editor' ) }
					isPressed={ shown?.nofollow === true }
					disabled={ shown === null }
					accessibleWhenDisabled
					onClick={ () => shown !== null && onChange( new LinkValue( shown.url, shown.newTab, ! shown.nofollow ) ) }
				/>
			</FrameActions>
			<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ control.label }
					hideLabelFromVision
					type="url"
					placeholder="https://"
					value={ url }
					onChange={ ( value: string ) => {
						setDraft( value );
						if ( value.trim() === '' ) {
							onChange( null );
						} else if ( isAllowedUrl( value ) ) {
							onChange( new LinkValue( value.trim(), shown?.newTab ?? false, shown?.nofollow ?? false ) );
						}
					} }
				/>
			</Row>
		</>
	);
}
