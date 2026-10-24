/**
 * Editor UI for SelectInput. Shows the value at the active breakpoint; when
 * unset there, shows what is inherited (or the fallback) and offers to
 * clear back to it.
 */
import { SelectControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import type { ControlFieldProps } from '../../../Inspector/control-field';
import { KeywordValue } from '../../../../Values/keyword';
import { Scope } from '../../block-definition';
import type { SelectInput } from './select-input';

interface SelectFieldProps extends ControlFieldProps {
	readonly input: SelectInput;
}

export function SelectField( { control, input, values, breakpoint, breakpoints, onChange }: SelectFieldProps ): ReactElement {
	const isStatic = control.scope === Scope.Static;
	const at = isStatic ? breakpoints.base.id : breakpoint.id;
	const responsive = values.get( control.id );

	const own = responsive.at( at );
	const resolved = responsive.resolve( at, breakpoints );
	const inherited = own === null && resolved instanceof KeywordValue ? resolved.keyword : null;
	const source = inherited === null ? null : breakpoints.get( responsive.source( at, breakpoints ) ?? at );

	const labelOf = ( value: string ): string => input.options.find( ( option ) => option.value === value )?.label ?? value;

	let unsetLabel: string = __( 'Not set', 'fb-editor' );
	if ( inherited !== null ) {
		/* translators: %s: inherited option label */
		unsetLabel = sprintf( __( 'Inherit (%s)', 'fb-editor' ), labelOf( inherited ) );
	} else if ( input.fallback !== null ) {
		/* translators: %s: fallback option label */
		unsetLabel = sprintf( __( 'Default (%s)', 'fb-editor' ), labelOf( input.fallback ) );
	}

	return (
		<SelectControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ control.label }
			value={ own instanceof KeywordValue ? own.keyword : '' }
			options={ [
				{ value: '', label: unsetLabel },
				...input.options.map( ( option ) => ( { value: option.value, label: option.label } ) ),
			] }
			onChange={ ( value: string ) => onChange( value === '' ? null : new KeywordValue( value ) ) }
			help={
				source !== null && source !== undefined
					? /* translators: %s: breakpoint label */ sprintf( __( 'Inherited from %s', 'fb-editor' ), source.label )
					: undefined
			}
		/>
	);
}
