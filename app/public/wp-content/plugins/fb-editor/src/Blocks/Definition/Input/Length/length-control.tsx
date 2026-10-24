/**
 * Editor UI for one length: a discrete slider over a theme scale, or a
 * custom number + unit (and `auto` where allowed), with inherited state and
 * reset. Used on its own by LengthInput, and once per row by SpacingInput.
 */
import { Button, RangeControl, SelectControl, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { reset, settings as customIcon } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { settings } from '../../../../Editor/settings';
import type { Preset } from '../../../../Presets/presets';
import { AutoLength, CustomLength, PresetReference, Unit, type Length } from '../../../../Values/length';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { Scope } from '../../block-definition';
import type { LengthInput, LengthRules } from './length-input';
import './length-control.scss';

/** A length as read from the value: set, unset, or differing across sides. */
export type LengthReading = Length | null | 'mixed';

interface LengthRowProps {
	readonly label: string;
	readonly own: LengthReading;
	readonly inherited: LengthReading;
	readonly sourceLabel: string | null;
	readonly rules: LengthRules;
	readonly scale: readonly Preset[];
	/** Null clears the row at this breakpoint. */
	readonly onChange: ( length: Length | null ) => void;
}

export function LengthRow( { label, own, inherited, sourceLabel, rules, scale, onChange }: LengthRowProps ): ReactElement {
	const shown = own === 'mixed' ? null : own ?? ( inherited === 'mixed' ? null : inherited );
	const [ custom, setCustom ] = useState( scale.length === 0 || shown instanceof CustomLength || shown instanceof AutoLength );
	const isInherited = own === null && inherited !== null;

	const emit = ( length: Length ): void => {
		if ( rules.allows( length ) ) {
			onChange( length );
		}
	};

	let status: string | null = null;
	if ( own === 'mixed' || ( isInherited && inherited === 'mixed' ) ) {
		status = __( 'Mixed', 'fb-editor' );
	} else if ( isInherited && sourceLabel !== null ) {
		/* translators: %s: breakpoint label */
		status = sprintf( __( 'Inherited from %s', 'fb-editor' ), sourceLabel );
	}

	return (
		<div className={ isInherited ? 'fb-length is-inherited' : 'fb-length' }>
			<div className="fb-length__header">
				<span className="fb-length__label">{ label }</span>
				{ status !== null && <span className="fb-length__status">{ status }</span> }
				{ scale.length > 0 && (
					<Button
						size="small"
						icon={ customIcon }
						label={ custom ? __( 'Use the theme scale', 'fb-editor' ) : __( 'Use a custom value', 'fb-editor' ) }
						isPressed={ custom }
						onClick={ () => setCustom( ! custom ) }
					/>
				) }
				{ own !== null && (
					<Button size="small" icon={ reset } label={ __( 'Reset to inherited', 'fb-editor' ) } onClick={ () => onChange( null ) } />
				) }
			</div>
			{ custom ? (
				<CustomLengthInput label={ label } shown={ shown } rules={ rules } onChange={ emit } />
			) : (
				<ScaleSlider label={ label } shown={ shown } scale={ scale } onChange={ emit } />
			) }
		</div>
	);
}

interface ScaleSliderProps {
	readonly label: string;
	readonly shown: Length | null;
	readonly scale: readonly Preset[];
	readonly onChange: ( length: Length ) => void;
}

/**
 * Discrete slider over the scale, like core's spacing control: one mark
 * per step draws the bar in sections. Never remounted while in use (that
 * would cut drags short).
 */
function ScaleSlider( { label, shown, scale, onChange }: ScaleSliderProps ): ReactElement {
	const index = shown instanceof PresetReference ? scale.findIndex( ( preset ) => preset.slug === shown.slug ) : -1;
	const current = scale[ index ];
	const marks = scale.map( ( _preset, step ) => ( { value: step } ) );

	return (
		<div className="fb-length__scale">
			<RangeControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				hideLabelFromVision
				withInputField={ false }
				min={ 0 }
				max={ scale.length - 1 }
				step={ 1 }
				marks={ marks }
				initialPosition={ 0 }
				// Omitted when nothing is set anywhere: the bar shows no selection.
				{ ...( index === -1 ? {} : { value: index } ) }
				renderTooltipContent={ ( value?: number | string | null ) => scale[ Number( value ?? 0 ) ]?.name ?? '' }
				onChange={ ( value?: number ) => {
					const preset = scale[ value ?? -1 ];
					if ( preset !== undefined ) {
						onChange( new PresetReference( preset.kind, preset.slug ) );
					}
				} }
			/>
			<span className="fb-length__value">{ current === undefined ? '—' : `${ current.name } · ${ current.value }` }</span>
		</div>
	);
}

const AUTO = 'auto';

interface CustomLengthInputProps {
	readonly label: string;
	readonly shown: Length | null;
	readonly rules: LengthRules;
	readonly onChange: ( length: Length ) => void;
}

function CustomLengthInput( { label, shown, rules, onChange }: CustomLengthInputProps ): ReactElement {
	// Local text state: half-typed numbers ("-", "1.") must not be lost.
	const [ text, setText ] = useState( shown instanceof CustomLength ? String( shown.value ) : '' );
	const [ unit, setUnit ] = useState< string >( shown instanceof AutoLength ? AUTO : shown instanceof CustomLength ? shown.unit : Unit.Px );

	const emitNumber = ( nextText: string, nextUnit: string ): void => {
		const unitValue = Object.values( Unit ).find( ( candidate ) => candidate === nextUnit );
		const number = Number.parseFloat( nextText );
		if ( unitValue === undefined || ! Number.isFinite( number ) ) {
			return;
		}
		try {
			onChange( new CustomLength( number, unitValue ) );
		} catch {
			// Out of range: keep the text, store nothing.
		}
	};

	const units = [
		...Object.values( Unit ).map( ( value ) => ( { value, label: value } ) ),
		...( rules.allowAuto ? [ { value: AUTO, label: AUTO } ] : [] ),
	];

	return (
		<div className="fb-length__custom">
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				hideLabelFromVision
				type="number"
				step="any"
				min={ rules.allowNegative ? undefined : 0 }
				disabled={ unit === AUTO }
				value={ text }
				onChange={ ( value: string ) => {
					setText( value );
					emitNumber( value, unit );
				} }
			/>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Unit', 'fb-editor' ) }
				hideLabelFromVision
				value={ unit }
				options={ units }
				onChange={ ( value: string ) => {
					setUnit( value );
					if ( value === AUTO ) {
						onChange( new AutoLength() );
					} else {
						emitNumber( text, value );
					}
				} }
			/>
		</div>
	);
}

interface LengthFieldProps extends ControlFieldProps {
	readonly input: LengthInput;
}

/** Editor UI for LengthInput: one length row for the control. */
export function LengthField( { control, input, values, breakpoint, breakpoints, onChange }: LengthFieldProps ): ReactElement {
	const at = control.scope === Scope.Static ? breakpoints.base.id : breakpoint.id;
	const responsive = values.get( control.id );
	const own = asLength( responsive.at( at ) );
	const resolved = asLength( responsive.resolve( at, breakpoints ) );
	const source = own === null && resolved !== null ? breakpoints.get( responsive.source( at, breakpoints ) ?? at ) : undefined;

	return (
		<LengthRow
			// Remount per breakpoint: local edit state must not leak across.
			key={ at }
			label={ control.label }
			own={ own }
			inherited={ own === null ? resolved : null }
			sourceLabel={ source?.label ?? null }
			rules={ input }
			scale={ input.scale === null ? [] : settings.presets.ofKind( input.scale ) }
			onChange={ onChange }
		/>
	);
}

function asLength( value: unknown ): Length | null {
	return value instanceof PresetReference || value instanceof CustomLength || value instanceof AutoLength ? value : null;
}
