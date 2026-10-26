/**
 * Editor UI for PresetInput, on one line: a select over the theme's
 * presets of the input's kind (e.g. font families). Unset here: the first
 * option names what applies instead.
 */
import { SelectControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { settings } from '../../../../Editor/settings';
import { PresetReference } from '../../../../Values/preset-reference';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { PresetInput } from './preset-input';

interface PresetFieldProps extends ControlFieldProps {
	readonly input: PresetInput;
}

export function PresetField( props: PresetFieldProps ): ReactElement {
	const { control, input, onChange } = props;
	const reading = readControl( props );
	const presets = settings.presets.ofKind( input.preset );

	const nameOf = ( slug: string ): string => presets.find( ( preset ) => preset.slug === slug )?.name ?? slug;
	const own = reading.own instanceof PresetReference ? reading.own.slug : '';
	const unsetLabel =
		reading.inherited instanceof PresetReference
			? /* translators: %s: inherited preset name */ sprintf( __( 'Inherit (%s)', 'fb-editor' ), nameOf( reading.inherited.slug ) )
			: __( 'Not set', 'fb-editor' );

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ control.label }
				hideLabelFromVision
				value={ own }
				options={ [ { value: '', label: unsetLabel }, ...presets.map( ( preset ) => ( { value: preset.slug, label: preset.name } ) ) ] }
				onChange={ ( slug: string ) => onChange( slug === '' ? null : new PresetReference( input.preset, slug ) ) }
			/>
		</Row>
	);
}
