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
import {
	cornerAll,
	cornerBottomLeft,
	cornerBottomRight,
	cornerTopLeft,
	cornerTopRight,
	sidesAll,
	sidesAxial,
	sidesBottom,
	sidesHorizontal,
	sidesLeft,
	sidesRight,
	sidesTop,
	sidesVertical,
} from '@wordpress/icons';
import type { ReactElement } from 'react';

import { settings } from '../../../../Editor/settings';
import { controlKey, store } from '../../../../Editor/store';
import type { BoxValue } from '../../../../Values/box';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { inheritedFrom } from '../../../Inspector/control-reading';
import { FrameActions } from '../../../Inspector/frame-actions';
import { Scope } from '../../block-definition';
import { LengthRow } from '../Length/length-control';
import { BoxShape, SpacingMode, type SpacingInput } from './spacing-input';
import { asBox, inferMode, RowId, rowsFor, rowSource, rowValue, withRow } from './spacing';
import './spacing-control.scss';

export const MODE_ICONS: Record< SpacingMode, ReactElement > = {
	[ SpacingMode.Linked ]: sidesAll,
	[ SpacingMode.Axis ]: sidesAxial,
	[ SpacingMode.Sides ]: sidesHorizontal,
};

export function modeLabel( mode: SpacingMode, shape: BoxShape ): string {
	switch ( mode ) {
		case SpacingMode.Linked:
			return shape === BoxShape.Corners ? __( 'All corners', 'fb-editor' ) : __( 'All sides', 'fb-editor' );
		case SpacingMode.Axis:
			return __( 'Vertical and horizontal', 'fb-editor' );
		case SpacingMode.Sides:
			return shape === BoxShape.Corners ? __( 'Each corner', 'fb-editor' ) : __( 'Each side', 'fb-editor' );
	}
}

export const ROW_ICONS: Record< RowId, ReactElement > = {
	[ RowId.All ]: sidesAll,
	[ RowId.Vertical ]: sidesVertical,
	[ RowId.Horizontal ]: sidesHorizontal,
	[ RowId.Top ]: sidesTop,
	[ RowId.Right ]: sidesRight,
	[ RowId.Bottom ]: sidesBottom,
	[ RowId.Left ]: sidesLeft,
};

/** Corners, clockwise from the top left: the box's top, right, bottom, left. */
const CORNER_ICONS: Partial< Record< RowId, ReactElement > > = {
	[ RowId.All ]: cornerAll,
	[ RowId.Top ]: cornerTopLeft,
	[ RowId.Right ]: cornerTopRight,
	[ RowId.Bottom ]: cornerBottomRight,
	[ RowId.Left ]: cornerBottomLeft,
};

function cornerLabel( row: RowId ): string {
	switch ( row ) {
		case RowId.Top:
			return __( 'Top left', 'fb-editor' );
		case RowId.Right:
			return __( 'Top right', 'fb-editor' );
		case RowId.Bottom:
			return __( 'Bottom right', 'fb-editor' );
		case RowId.Left:
			return __( 'Bottom left', 'fb-editor' );
		default:
			return __( 'All corners', 'fb-editor' );
	}
}

export function rowLabel( row: RowId, shape: BoxShape ): string {
	if ( shape === BoxShape.Corners ) {
		return cornerLabel( row );
	}
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
	const { definition, control, input, values, fallbacks, breakpoint, breakpoints, onChange } = props;
	const at = control.scope === Scope.Static ? breakpoints.base.id : breakpoint.id;
	const responsive = values.get( control.id );
	const own = asBox( responsive.at( at ) );
	// Per side: this value (own or from a larger breakpoint), else the
	// first fallback with that side.
	const fallbackBoxes = fallbacks.sources.map( ( source ) => ( {
		box: asBox( source.values.get( control.id ).resolve( at, breakpoints ) ),
		status: source.status,
	} ) );
	const resolved = [ asBox( responsive.resolve( at, breakpoints ) ), ...fallbackBoxes.map( ( { box } ) => box ) ].reduce< BoxValue | null >(
		( strong, weak ) => ( strong === null ? weak : weak === null ? strong : strong.inheritFrom( weak ) ),
		null
	);

	const key = controlKey( definition.name, control.id );
	const remembered = useSelect( ( select ) => select( store ).getSpacingMode( key ), [ key ] );
	const { setSpacingMode } = useDispatch( store );
	const mode = remembered !== null && input.modes.includes( remembered ) ? remembered : inferMode( own ?? resolved, input.modes );

	const scale = input.scale === null ? [] : settings.presets.ofKind( input.scale );
	const rowIcon = ( row: RowId ): ReactElement | undefined => ( input.shape === BoxShape.Corners ? CORNER_ICONS[ row ] : ROW_ICONS[ row ] );

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
							icon={ input.shape === BoxShape.Corners && candidate === SpacingMode.Linked ? cornerAll : MODE_ICONS[ candidate ] }
							label={ modeLabel( candidate, input.shape ) }
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
				} else {
					status = fallbackBoxes.find( ( { box } ) => rowValue( box, row.sides ) !== null )?.status ?? null;
				}
				return (
					<LengthRow
						// Remount per breakpoint: local edit state must not leak across.
						key={ `${ at }/${ row.id }` }
						label={ rowLabel( row.id, input.shape ) }
						{ ...( mode === SpacingMode.Linked ? {} : { icon: <Icon icon={ rowIcon( row.id ) ?? ROW_ICONS[ row.id ] } size={ 20 } /> } ) }
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
