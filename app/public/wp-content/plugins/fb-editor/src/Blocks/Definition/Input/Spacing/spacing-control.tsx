/**
 * Editor UI for SpacingInput:
 * - Linked / Axis / Sides mode buttons (only the modes the input allows;
 *   the pick is remembered per block type + control, in memory);
 * - per row: a slider over the theme's spacing scale, or a custom number +
 *   unit (and `auto` where allowed);
 * - an unset row shows the inherited value, greyed, with its source;
 * - a reset button clears the row at the active breakpoint.
 *
 * Every value goes through SpacingInput.allows(): the UI can't store what
 * PHP would drop.
 */
import { Button, RangeControl, SelectControl, TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { reset, settings as customIcon, sidesAll, sidesAxial, sidesHorizontal } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { settings } from '../../../../Editor/settings';
import { controlKey, store } from '../../../../Editor/store';
import { PresetKind, type Preset } from '../../../../Presets/presets';
import { AutoLength, CustomLength, PresetLength, Unit, type Length } from '../../../../Values/length';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { Scope } from '../../block-definition';
import { SpacingMode, type SpacingInput } from './spacing-input';
import { asBox, inferMode, RowId, rowsFor, rowSource, rowValue, withRow, type RowValue } from './spacing';
import './spacing-control.scss';

const MODE_ICONS: Record< SpacingMode, ReactElement > = {
	[ SpacingMode.Linked ]: sidesAll,
	[ SpacingMode.Axis ]: sidesAxial,
	[ SpacingMode.Sides ]: sidesHorizontal,
};

function modeLabel( mode: SpacingMode ): string {
	switch ( mode ) {
		case SpacingMode.Linked:
			return __( 'All sides', 'fb-editor' );
		case SpacingMode.Axis:
			return __( 'Vertical and horizontal', 'fb-editor' );
		case SpacingMode.Sides:
			return __( 'Each side', 'fb-editor' );
	}
}

function rowLabel( row: RowId ): string {
	switch ( row ) {
		case RowId.All:
			return __( 'All', 'fb-editor' );
		case RowId.Vertical:
			return __( 'Vertical', 'fb-editor' );
		case RowId.Horizontal:
			return __( 'Horizontal', 'fb-editor' );
		case RowId.Top:
			return __( 'Top', 'fb-editor' );
		case RowId.Right:
			return __( 'Right', 'fb-editor' );
		case RowId.Bottom:
			return __( 'Bottom', 'fb-editor' );
		case RowId.Left:
			return __( 'Left', 'fb-editor' );
	}
}

interface SpacingFieldProps extends ControlFieldProps {
	readonly input: SpacingInput;
}

export function SpacingField( props: SpacingFieldProps ): ReactElement {
	const { definition, control, input, values, breakpoint, breakpoints, onChange } = props;
	const at = control.scope === Scope.Static ? breakpoints.base.id : breakpoint.id;
	const responsive = values.get( control.id );
	const own = asBox( responsive.at( at ) );
	const resolved = asBox( responsive.resolve( at, breakpoints ) );

	const key = controlKey( definition.name, control.id );
	const remembered = useSelect( ( select ) => select( store ).getSpacingMode( key ), [ key ] );
	const { setSpacingMode } = useDispatch( store );
	const mode = remembered !== null && input.modes.includes( remembered ) ? remembered : inferMode( own ?? resolved, input.modes );

	const scale = settings.presets.ofKind( PresetKind.Spacing );

	return (
		<fieldset className="fb-spacing">
			<div className="fb-spacing__header">
				<legend className="fb-spacing__label">{ control.label }</legend>
				{ input.modes.length > 1 && (
					<div className="fb-spacing__modes">
						{ input.modes.map( ( candidate ) => (
							<Button
								key={ candidate }
								size="small"
								icon={ MODE_ICONS[ candidate ] }
								label={ modeLabel( candidate ) }
								isPressed={ candidate === mode }
								onClick={ () => setSpacingMode( key, candidate ) }
							/>
						) ) }
					</div>
				) }
			</div>
			{ rowsFor( mode ).map( ( row ) => {
				const ownRow = rowValue( own, row.sides );
				const source = ownRow === null ? rowSource( responsive, row.sides, at, breakpoints ) : null;
				return (
					<LengthRow
						// Remount per breakpoint: local edit state must not leak across.
						key={ `${ at }/${ row.id }` }
						label={ rowLabel( row.id ) }
						own={ ownRow }
						inherited={ ownRow === null ? rowValue( resolved, row.sides ) : null }
						sourceLabel={ source === null ? null : breakpoints.get( source )?.label ?? null }
						input={ input }
						scale={ scale }
						onChange={ ( length ) => onChange( withRow( own, row.sides, length ) ) }
					/>
				);
			} ) }
		</fieldset>
	);
}

interface LengthRowProps {
	readonly label: string;
	readonly own: RowValue;
	readonly inherited: RowValue;
	readonly sourceLabel: string | null;
	readonly input: SpacingInput;
	readonly scale: readonly Preset[];
	/** Null clears the row at this breakpoint. */
	readonly onChange: ( length: Length | null ) => void;
}

function LengthRow( { label, own, inherited, sourceLabel, input, scale, onChange }: LengthRowProps ): ReactElement {
	const shown = own === 'mixed' ? null : own ?? ( inherited === 'mixed' ? null : inherited );
	const [ custom, setCustom ] = useState( scale.length === 0 || shown instanceof CustomLength || shown instanceof AutoLength );
	const isInherited = own === null && inherited !== null;

	const emit = ( length: Length ): void => {
		if ( input.allows( length ) ) {
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
		<div className={ isInherited ? 'fb-spacing__row is-inherited' : 'fb-spacing__row' }>
			<div className="fb-spacing__row-header">
				<span className="fb-spacing__row-label">{ label }</span>
				{ status !== null && <span className="fb-spacing__status">{ status }</span> }
				{ scale.length > 0 && (
					<Button
						size="small"
						icon={ customIcon }
						label={ custom ? __( 'Use the spacing scale', 'fb-editor' ) : __( 'Use a custom value', 'fb-editor' ) }
						isPressed={ custom }
						onClick={ () => setCustom( ! custom ) }
					/>
				) }
				{ own !== null && (
					<Button size="small" icon={ reset } label={ __( 'Reset to inherited', 'fb-editor' ) } onClick={ () => onChange( null ) } />
				) }
			</div>
			{ custom ? (
				<CustomLengthInput label={ label } shown={ shown } input={ input } onChange={ emit } />
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
	const index = shown instanceof PresetLength ? scale.findIndex( ( preset ) => preset.slug === shown.slug ) : -1;
	const current = scale[ index ];
	const marks = scale.map( ( _preset, step ) => ( { value: step } ) );

	return (
		<div className="fb-spacing__scale">
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
						onChange( new PresetLength( PresetKind.Spacing, preset.slug ) );
					}
				} }
			/>
			<span className="fb-spacing__value">{ current === undefined ? '—' : `${ current.name } · ${ current.value }` }</span>
		</div>
	);
}

const AUTO = 'auto';

interface CustomLengthInputProps {
	readonly label: string;
	readonly shown: Length | null;
	readonly input: SpacingInput;
	readonly onChange: ( length: Length ) => void;
}

function CustomLengthInput( { label, shown, input, onChange }: CustomLengthInputProps ): ReactElement {
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
		...( input.allowAuto ? [ { value: AUTO, label: AUTO } ] : [] ),
	];

	return (
		<div className="fb-spacing__custom">
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				hideLabelFromVision
				type="number"
				step="any"
				min={ input.allowNegative ? undefined : 0 }
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
