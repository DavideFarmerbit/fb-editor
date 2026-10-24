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
import { Button, VisuallyHidden } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { sidesAll, sidesAxial, sidesHorizontal } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { settings } from '../../../../Editor/settings';
import { controlKey, store } from '../../../../Editor/store';
import type { ControlFieldProps } from '../../../Inspector/control-field';
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

	const scale = settings.presets.ofKind( input.scale );

	return (
		<fieldset className="fb-spacing">
			{ /* The control frame shows the visible label. */ }
			<VisuallyHidden as="legend">{ control.label }</VisuallyHidden>
			<div className="fb-spacing__header">
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
						rules={ input }
						scale={ scale }
						onChange={ ( length ) => onChange( withRow( own, row.sides, length ) ) }
					/>
				);
			} ) }
		</fieldset>
	);
}
