/**
 * Editor UI for one length, on one line: a discrete slider over a theme
 * scale, or a number + unit box; then the custom toggle and reset. Used on
 * its own by LengthInput, and once per row by SpacingInput.
 */
import { Button, RangeControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { settings as customIcon } from '@wordpress/icons';
import type { ReactElement, ReactNode } from 'react';

import { settings } from '../../../../Editor/settings';
import type { Preset } from '../../../../Presets/presets';
import { AutoLength, CustomLength, PresetReference, Unit, type Length } from '../../../../Values/length';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { LengthInput, LengthRules } from './length-input';
import { UnitInput } from './unit-input';

/** A length as read from the value: set, unset, or differing across sides. */
export type LengthReading = Length | null | 'mixed';

interface LengthRowProps {
	readonly label: string;
	/** Leading icon (spacing rows: which sides). */
	readonly icon?: ReactNode;
	readonly own: LengthReading;
	readonly inherited: LengthReading;
	/** "Inherited from XL" / "From Section default" while not own. */
	readonly status: string | null;
	readonly rules: LengthRules;
	readonly scale: readonly Preset[];
	/** Null clears the row at this breakpoint. */
	readonly onChange: ( length: Length | null ) => void;
}

function asCustom( length: LengthReading ): CustomLength | AutoLength | null {
	return length instanceof CustomLength || length instanceof AutoLength ? length : null;
}

/** What the slider's 0 step writes (see LengthRules.zeroStep). */
const ZERO = new CustomLength( 0, Unit.Px );

function isZero( length: Length | null ): boolean {
	return length instanceof CustomLength && length.value === 0;
}

export function LengthRow( { label, icon, own, inherited, status: inheritedStatus, rules, scale, onChange }: LengthRowProps ): ReactElement {
	const shown = own === 'mixed' ? null : own ?? ( inherited === 'mixed' ? null : inherited );
	// A 0 is shown on the slider's 0 step, when it has one.
	const onSlider = shown instanceof PresetReference || shown === null || ( rules.zeroStep && isZero( shown ) );
	const [ custom, setCustom ] = useState( scale.length === 0 || ! onSlider );
	const isInherited = own === null && inherited !== null;

	const emit = ( length: Length ): void => {
		if ( rules.allows( length ) ) {
			onChange( length );
		}
	};

	let status: string | null = null;
	if ( own === 'mixed' || ( isInherited && inherited === 'mixed' ) ) {
		status = __( 'Mixed', 'fb-editor' );
	} else if ( isInherited ) {
		status = inheritedStatus;
	}

	return (
		<Row
			{ ...( icon === undefined ? {} : { icon } ) }
			status={ status }
			inherited={ isInherited }
			actions={
				scale.length > 0 && (
					<Button
						size="small"
						icon={ customIcon }
						label={ custom ? __( 'Use the theme scale', 'fb-editor' ) : __( 'Use a custom value', 'fb-editor' ) }
						isPressed={ custom }
						onClick={ () => setCustom( ! custom ) }
					/>
				)
			}
			onReset={ own === null ? null : () => onChange( null ) }
		>
			{ custom ? (
				<UnitInput
					label={ label }
					value={ asCustom( own ) }
					placeholder={ own === null ? asCustom( inherited ) : null }
					rules={ rules }
					onChange={ emit }
				/>
			) : (
				<ScaleSlider label={ label } shown={ shown } scale={ scale } zeroStep={ rules.zeroStep } onChange={ emit } />
			) }
		</Row>
	);
}

interface ScaleSliderProps {
	readonly label: string;
	readonly shown: Length | null;
	readonly scale: readonly Preset[];
	/** Step 0 is a plain 0, before the scale's first preset. */
	readonly zeroStep: boolean;
	readonly onChange: ( length: Length ) => void;
}

/**
 * Discrete slider over the scale, like core's spacing control: one mark
 * per step draws the bar in sections. Never remounted while in use (that
 * would cut drags short). Step name and value are in the tooltip.
 */
function ScaleSlider( { label, shown, scale, zeroStep, onChange }: ScaleSliderProps ): ReactElement {
	// Step → what it writes: the 0 step (if any), then the presets.
	const steps: ReadonlyArray< { readonly length: Length; readonly tooltip: string } > = [
		...( zeroStep ? [ { length: ZERO, tooltip: '0' } ] : [] ),
		...scale.map( ( preset ) => ( { length: new PresetReference( preset.kind, preset.slug ), tooltip: `${ preset.name } · ${ preset.value }` } ) ),
	];
	const index = steps.findIndex( ( step ) =>
		step.length instanceof PresetReference ? shown instanceof PresetReference && shown.slug === step.length.slug : isZero( shown )
	);
	const marks = steps.map( ( _step, step ) => ( { value: step } ) );

	return (
		<RangeControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			hideLabelFromVision
			withInputField={ false }
			min={ 0 }
			max={ steps.length - 1 }
			step={ 1 }
			marks={ marks }
			initialPosition={ 0 }
			// Omitted when nothing is set anywhere: the bar shows no selection.
			{ ...( index === -1 ? {} : { value: index } ) }
			renderTooltipContent={ ( value?: number | string | null ) => steps[ Number( value ?? 0 ) ]?.tooltip ?? '' }
			onChange={ ( value?: number ) => {
				const step = steps[ value ?? -1 ];
				if ( step !== undefined ) {
					onChange( step.length );
				}
			} }
		/>
	);
}

interface LengthFieldProps extends ControlFieldProps {
	readonly input: LengthInput;
}

/** Editor UI for LengthInput: one length row for the control. */
export function LengthField( props: LengthFieldProps ): ReactElement {
	const { control, input, onChange } = props;
	const reading = readControl( props );

	return (
		<LengthRow
			// Remount per breakpoint: local edit state must not leak across.
			key={ reading.at }
			label={ control.label }
			own={ asLength( reading.own ) }
			inherited={ asLength( reading.inherited ) }
			status={ reading.status }
			rules={ input }
			scale={ input.scale === null ? [] : settings.presets.ofKind( input.scale ) }
			onChange={ onChange }
		/>
	);
}

function asLength( value: unknown ): Length | null {
	return value instanceof PresetReference || value instanceof CustomLength || value instanceof AutoLength ? value : null;
}
