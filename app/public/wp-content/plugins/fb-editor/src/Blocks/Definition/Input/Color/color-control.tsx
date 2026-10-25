/**
 * Editor UI for ColorInput, on one line: the theme palette as swatches
 * (stored as preset references), a custom color picker when the input
 * allows it, and reset. Unset here: the color that applies is shown greyed.
 */
import { Button, ColorPicker, Dropdown } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { settings } from '../../../../Editor/settings';
import { PresetKind } from '../../../../Presets/presets';
import { CustomColor, type Color } from '../../../../Values/color';
import { PresetReference } from '../../../../Values/preset-reference';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { ColorInput } from './color-input';
import './color-control.scss';

interface ColorFieldProps extends ControlFieldProps {
	readonly input: ColorInput;
}

function asColor( value: unknown ): Color | null {
	return value instanceof PresetReference || value instanceof CustomColor ? value : null;
}

export function ColorField( props: ColorFieldProps ): ReactElement {
	const { control, input, onChange } = props;
	const reading = readControl( props );
	const own = asColor( reading.own );
	const shown = asColor( reading.shown );
	const palette = settings.presets.ofKind( PresetKind.Color );

	const emit = ( color: Color ): void => {
		if ( input.allows( color ) ) {
			onChange( color );
		}
	};

	const isSelected = ( slug: string ): boolean => shown instanceof PresetReference && shown.slug === slug;
	const custom = shown instanceof CustomColor ? shown : null;

	return (
		<Row status={ reading.status } inherited={ own === null && shown !== null } onReset={ own === null ? null : () => onChange( null ) }>
			<div className="fb-color__swatches" role="listbox" aria-label={ control.label }>
				{ palette.map( ( preset ) => (
					<Button
						key={ preset.slug }
						className="fb-color__swatch"
						role="option"
						aria-selected={ isSelected( preset.slug ) }
						isPressed={ isSelected( preset.slug ) }
						label={ preset.name }
						showTooltip
						style={ { background: preset.value } }
						onClick={ () => emit( new PresetReference( PresetKind.Color, preset.slug ) ) }
					/>
				) ) }
				{ input.allowCustom && (
					<Dropdown
						popoverProps={ { placement: 'left-start' } }
						renderToggle={ ( { isOpen, onToggle } ) => (
							<Button
								className="fb-color__swatch is-custom"
								isPressed={ custom !== null }
								aria-expanded={ isOpen }
								label={ custom === null ? __( 'Custom color', 'fb-editor' ) : custom.hex }
								showTooltip
								style={ custom === null ? {} : { background: custom.hex } }
								onClick={ onToggle }
							/>
						) }
						renderContent={ () => (
							<ColorPicker
								color={ custom?.hex ?? '#000000' }
								enableAlpha
								onChange={ ( hex: string ) => {
									try {
										emit( new CustomColor( hex ) );
									} catch {
										// Not a hex color: ignore.
									}
								} }
							/>
						) }
					/>
				) }
			</div>
		</Row>
	);
}
