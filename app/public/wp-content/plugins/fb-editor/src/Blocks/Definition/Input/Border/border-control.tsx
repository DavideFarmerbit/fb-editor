/**
 * Editor UI for BorderInput, like WordPress's own border control: the mode
 * (all sides / vertical + horizontal / each side, as for padding) in the
 * control's header line, then one line per row: a swatch showing the row's
 * color and style (it opens the palette and the style buttons) and the
 * width box, then reset. Setting a width or color on a row with no style
 * anywhere also makes it solid: a border needs one to show at all.
 */
import { Button, ColorPicker, Dropdown, Icon } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { settings } from '../../../../Editor/settings';
import { controlKey, store } from '../../../../Editor/store';
import { PresetKind } from '../../../../Presets/presets';
import { BorderBox, BorderSide } from '../../../../Values/border';
import { CustomColor, type Color } from '../../../../Values/color';
import { CustomLength, type Length } from '../../../../Values/length';
import { PresetReference } from '../../../../Values/preset-reference';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { inheritedFrom } from '../../../Inspector/control-reading';
import { FrameActions } from '../../../Inspector/frame-actions';
import { Row } from '../../../Inspector/row';
import { Scope } from '../../block-definition';
import type { LengthRules } from '../Length/length-input';
import { UnitInput } from '../Length/unit-input';
import { RowId, rowsFor } from '../Spacing/spacing';
import { MODE_ICONS, modeLabel, ROW_ICONS, rowLabel } from '../Spacing/spacing-control';
import { BoxShape, SpacingMode } from '../Spacing/spacing-input';
import { asBorder, borderRowSource, borderRowValue, inferBorderMode, withBorderRow, type BorderRowValue } from './border-rows';
import type { BorderInput } from './border-input';
import './border-control.scss';

const SOLID = 'solid';

/** Width: a custom length, zero or more. */
const WIDTH_RULES: LengthRules = {
	allowAuto: false,
	allowNegative: false,
	scale: null,
	zeroStep: false,
	allows: ( length: Length ): boolean => length instanceof CustomLength && ! length.isNegative(),
};

/** What a color looks like here (presets: their palette value). */
function cssColor( color: Color | null ): string | undefined {
	if ( color instanceof CustomColor ) {
		return color.hex;
	}
	return color === null ? undefined : settings.presets.ofKind( PresetKind.Color ).find( ( preset ) => preset.slug === color.slug )?.value;
}

interface SwatchProps {
	readonly input: BorderInput;
	/** What the row shows (own, else inherited). */
	readonly shown: BorderSide | null;
	readonly onColor: ( color: Color | null ) => void;
	readonly onStyle: ( style: string | null ) => void;
}

function BorderSwatch( { input, shown, onColor, onStyle }: SwatchProps ): ReactElement {
	const palette = settings.presets.ofKind( PresetKind.Color );
	const color = shown?.color ?? null;
	const custom = color instanceof CustomColor ? color : null;
	const style = shown?.style ?? null;

	return (
		<Dropdown
			popoverProps={ { placement: 'left-start' } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					className="fb-border__swatch"
					aria-expanded={ isOpen }
					label={ __( 'Border color and style', 'fb-editor' ) }
					showTooltip
					onClick={ onToggle }
				>
					<span
						className={ color === null && style === null ? 'fb-border__dot is-empty' : 'fb-border__dot' }
						style={ { border: `3px ${ style === null || style === 'none' ? 'solid' : style } ${ cssColor( color ) ?? 'currentColor' }` } }
					/>
				</Button>
			) }
			renderContent={ () => (
				<div className="fb-border__popover">
					<div className="fb-color__swatches" role="listbox" aria-label={ __( 'Color', 'fb-editor' ) }>
						{ palette.map( ( preset ) => {
							const selected = color instanceof PresetReference && color.slug === preset.slug;
							return (
								<Button
									key={ preset.slug }
									className="fb-color__swatch"
									role="option"
									aria-selected={ selected }
									label={ preset.name }
									showTooltip
									style={ { background: preset.value } }
									onClick={ () => onColor( new PresetReference( PresetKind.Color, preset.slug ) ) }
								/>
							);
						} ) }
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
											onColor( new CustomColor( hex ) );
										} catch {
											// Not a hex color: ignore.
										}
									} }
								/>
							) }
						/>
					</div>
					<p className="fb-border__heading">{ __( 'Style', 'fb-editor' ) }</p>
					<div className="fb-border__styles" role="radiogroup" aria-label={ __( 'Style', 'fb-editor' ) }>
						{ input.styles.map( ( option ) => (
							<Button
								key={ option.value }
								className="fb-border__style"
								role="radio"
								aria-checked={ style === option.value }
								label={ option.label }
								showTooltip
								onClick={ () => onStyle( option.value ) }
							>
								<span className="fb-border__line" style={ { borderTop: `3px ${ option.value === 'none' ? 'hidden' : option.value } currentColor` } } />
							</Button>
						) ) }
					</div>
					<Button
						className="fb-border__reset"
						variant="tertiary"
						size="compact"
						onClick={ () => {
							onColor( null );
							onStyle( null );
						} }
					>
						{ __( 'Reset color and style', 'fb-editor' ) }
					</Button>
				</div>
			) }
		/>
	);
}

interface BorderFieldProps extends ControlFieldProps {
	readonly input: BorderInput;
}

export function BorderField( props: BorderFieldProps ): ReactElement {
	const { definition, control, input, values, fallbacks, breakpoint, breakpoints, onChange } = props;
	const at = control.scope === Scope.Static ? breakpoints.base.id : breakpoint.id;
	const responsive = values.get( control.id );
	const own = asBorder( responsive.at( at ) );
	// Per side and part: this value (own or from a larger breakpoint), else
	// the first fallback that has it.
	const fallbackBoxes = fallbacks.sources.map( ( source ) => ( {
		box: asBorder( source.values.get( control.id ).resolve( at, breakpoints ) ),
		status: source.status,
	} ) );
	const resolved = [ asBorder( responsive.resolve( at, breakpoints ) ), ...fallbackBoxes.map( ( { box } ) => box ) ].reduce< BorderBox | null >(
		( strong, weak ) => ( strong === null ? weak : weak === null ? strong : strong.inheritFrom( weak ) ),
		null
	);

	const key = controlKey( definition.name, control.id );
	const remembered = useSelect( ( select ) => select( store ).getSpacingMode( key ), [ key ] );
	const { setSpacingMode } = useDispatch( store );
	const mode = remembered !== null && input.modes.includes( remembered ) ? remembered : inferBorderMode( own ?? resolved, input.modes );

	return (
		<fieldset className="fb-border">
			{ input.modes.length > 1 && (
				<FrameActions>
					{ input.modes.map( ( candidate ) => (
						<Button
							key={ candidate }
							size="small"
							icon={ MODE_ICONS[ candidate ] }
							label={ modeLabel( candidate, BoxShape.Sides ) }
							isPressed={ candidate === mode }
							onClick={ () => setSpacingMode( key, candidate ) }
						/>
					) ) }
				</FrameActions>
			) }
			{ rowsFor( mode ).map( ( row ) => {
				const ownRow: BorderRowValue = borderRowValue( own, row.sides );
				const shownRow: BorderRowValue = ownRow ?? borderRowValue( resolved, row.sides );
				const shown = shownRow === 'mixed' ? null : shownRow;
				const ownSide = ownRow === 'mixed' ? null : ownRow;

				let status: string | null = null;
				if ( ownRow === 'mixed' ) {
					status = __( 'Mixed', 'fb-editor' );
				} else if ( ownRow === null && shown !== null ) {
					const source = borderRowSource( responsive, row.sides, at, breakpoints );
					const sourceBreakpoint = source === null ? undefined : breakpoints.get( source );
					status =
						sourceBreakpoint !== undefined
							? inheritedFrom( sourceBreakpoint.label )
							: fallbackBoxes.find( ( { box } ) => borderRowValue( box, row.sides ) !== null )?.status ?? null;
				}

				// Writes the row's own side with one part changed; a border with
				// no style anywhere doesn't show, so a width or color brings solid.
				const write = ( change: ( side: BorderSide ) => BorderSide ): void => {
					const base = ownSide ?? new BorderSide( null, null, null );
					let next = change( base );
					if ( next.style === null && ( shown?.style ?? null ) === null && ( next.width !== null || next.color !== null ) ) {
						next = new BorderSide( next.width, SOLID, next.color );
					}
					onChange( withBorderRow( own, row.sides, next ) );
				};

				return (
					<Row
						key={ `${ at }/${ row.id }` }
						{ ...( mode === SpacingMode.Linked ? {} : { icon: <Icon icon={ ROW_ICONS[ row.id ] } size={ 20 } /> } ) }
						status={ status }
						inherited={ ownRow === null && shown !== null }
						onReset={ ownRow === null ? null : () => onChange( withBorderRow( own, row.sides, null ) ) }
					>
						<div className="fb-border__row">
							<BorderSwatch
								input={ input }
								shown={ shown }
								onColor={ ( color ) => write( ( side ) => new BorderSide( side.width, side.style, color ) ) }
								onStyle={ ( style ) => write( ( side ) => new BorderSide( side.width, style, side.color ) ) }
							/>
							<UnitInput
								label={ row.id === RowId.All ? control.label : rowLabel( row.id, BoxShape.Sides ) }
								value={ ownSide?.width ?? null }
								placeholder={ shown?.width ?? null }
								rules={ WIDTH_RULES }
								onChange={ ( length ) => {
									if ( length instanceof CustomLength ) {
										write( ( side ) => new BorderSide( length, side.style, side.color ) );
									}
								} }
							/>
						</div>
					</Row>
				);
			} ) }
		</fieldset>
	);
}
