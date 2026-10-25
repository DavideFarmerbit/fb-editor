/**
 * Editor UI for SpacingInput:
 * - mode buttons in the control's header line, for the modes the input
 *   allows (all sides / vertical + horizontal / each side); the pick is
 *   remembered per block type + control, in memory;
 * - one line per row (icon = which sides): slider or custom value, custom
 *   toggle, reset; per side, values fall back to larger breakpoints, then
 *   to the fallback (type or theme default).
 *
 * Every value goes through SpacingInput.allows(): the UI can't store what
 * PHP would drop.
 */
import { Button, Icon, VisuallyHidden } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { sidesAll, sidesAxial, sidesBottom, sidesHorizontal, sidesLeft, sidesRight, sidesTop, sidesVertical } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { settings } from '../../../../Editor/settings';
import { controlKey, store } from '../../../../Editor/store';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { inheritedFrom } from '../../../Inspector/control-reading';
import { FrameActions } from '../../../Inspector/frame-actions';
import { Scope } from '../../block-definition';
import { LengthRow } from '../Length/length-control';
import { SpacingMode, type SpacingInput } from './spacing-input';
import { asBox, inferMode, RowId, rowsFor, rowSource, rowValue, withRow } from './spacing';
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

const ROW_ICONS: Record< RowId, ReactElement > = {
	[ RowId.All ]: sidesAll,
	[ RowId.Vertical ]: sidesVertical,
	[ RowId.Horizontal ]: sidesHorizontal,
	[ RowId.Top ]: sidesTop,
	[ RowId.Right ]: sidesRight,
	[ RowId.Bottom ]: sidesBottom,
	[ RowId.Left ]: sidesLeft,
};

function rowLabel( row: RowId ): string {
	switch ( row ) {
		case RowId.All:
			return __( 'All sides', 'fb-editor' );
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
	const { definition, control, input, values, fallback, fallbackStatus, breakpoint, breakpoints, onChange } = props;
	const at = control.scope === Scope.Static ? breakpoints.base.id : breakpoint.id;
	const responsive = values.get( control.id );
	const own = asBox( responsive.at( at ) );
	// Per side: this value (own or from a larger breakpoint), else the fallback.
	const fromValues = asBox( responsive.resolve( at, breakpoints ) );
	const fromFallback = asBox( fallback.get( control.id ).resolve( at, breakpoints ) );
	const resolved = fromValues === null ? fromFallback : fromFallback === null ? fromValues : fromValues.inheritFrom( fromFallback );

	const key = controlKey( definition.name, control.id );
	const remembered = useSelect( ( select ) => select( store ).getSpacingMode( key ), [ key ] );
	const { setSpacingMode } = useDispatch( store );
	const mode = remembered !== null && input.modes.includes( remembered ) ? remembered : inferMode( own ?? resolved, input.modes );

	const scale = settings.presets.ofKind( input.scale );

	return (
		<fieldset className="fb-spacing">
			{ /* The control frame shows the visible label. */ }
			<VisuallyHidden as="legend">{ control.label }</VisuallyHidden>
			{ input.modes.length > 1 && (
				<FrameActions>
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
				</FrameActions>
			) }
			{ rowsFor( mode ).map( ( row ) => {
				const ownRow = rowValue( own, row.sides );
				const source = ownRow === null ? rowSource( responsive, row.sides, at, breakpoints ) : null;
				const sourceBreakpoint = source === null ? undefined : breakpoints.get( source );
				let status: string | null = null;
				if ( sourceBreakpoint !== undefined ) {
					status = inheritedFrom( sourceBreakpoint.label );
				} else if ( rowValue( fromFallback, row.sides ) !== null ) {
					status = fallbackStatus;
				}
				return (
					<LengthRow
						// Remount per breakpoint: local edit state must not leak across.
						key={ `${ at }/${ row.id }` }
						label={ rowLabel( row.id ) }
						{ ...( mode === SpacingMode.Linked ? {} : { icon: <Icon icon={ ROW_ICONS[ row.id ] } size={ 20 } /> } ) }
						own={ ownRow }
						inherited={ ownRow === null ? rowValue( resolved, row.sides ) : null }
						status={ status }
						rules={ input }
						scale={ scale }
						onChange={ ( length ) => onChange( withRow( own, row.sides, length ) ) }
					/>
				);
			} ) }
		</fieldset>
	);
}
