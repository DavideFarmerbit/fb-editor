/**
 * A number + unit in one box, like core's unit inputs: the unit sits inside
 * the field in the accent color and opens the unit list on click; typing a
 * unit ("10%", "2rem", "auto") selects it instead of keeping it as text.
 *
 * Built from a plain input and a native select (core's UnitControl is still
 * __experimental in WordPress 7.1).
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { AutoLength, CustomLength, Unit, type Length } from '../../../../Values/length';
import type { LengthRules } from './length-input';
import { parseTypedLength } from './typed-length';
import './unit-input.scss';

const AUTO = 'auto';
const UNITS: readonly string[] = Object.values( Unit );

interface UnitInputProps {
	readonly label: string;
	/** The own value; null when unset here. */
	readonly value: CustomLength | AutoLength | null;
	/** Shown greyed while there's no own value (what applies instead). */
	readonly placeholder: CustomLength | AutoLength | null;
	readonly rules: LengthRules;
	readonly onChange: ( length: Length ) => void;
}

function textOf( value: CustomLength | AutoLength | null ): string {
	return value instanceof CustomLength ? String( value.value ) : '';
}

function unitOf( value: CustomLength | AutoLength | null ): string | null {
	if ( value instanceof AutoLength ) {
		return AUTO;
	}
	return value instanceof CustomLength ? value.unit : null;
}

export function UnitInput( { label, value, placeholder, rules, onChange }: UnitInputProps ): ReactElement {
	const input = useRef< HTMLInputElement >( null );
	// Local text: half-typed numbers ("-", "1.") must not be lost.
	const [ text, setText ] = useState( textOf( value ) );
	const [ unit, setUnit ] = useState( unitOf( value ) ?? unitOf( placeholder ) ?? Unit.Px );

	// Follow outside changes (reset, undo…) unless the user is typing here.
	const valueKey = JSON.stringify( value );
	useEffect( () => {
		if ( input.current !== null && input.current.ownerDocument.activeElement === input.current ) {
			return;
		}
		setText( textOf( value ) );
		setUnit( ( current ) => unitOf( value ) ?? current );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ valueKey ] );

	const emit = ( nextText: string, nextUnit: string ): void => {
		if ( nextUnit === AUTO ) {
			onChange( new AutoLength() );
			return;
		}
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

	const onType = ( typed: string ): void => {
		const parsed = parseTypedLength( typed, UNITS, rules.allowAuto );
		if ( parsed.auto ) {
			setText( '' );
			setUnit( AUTO );
			emit( '', AUTO );
			return;
		}
		// Typing a number while on "auto" goes back to a real unit.
		const nextUnit = parsed.unit ?? ( unit === AUTO ? Unit.Px : unit );
		setText( parsed.text );
		setUnit( nextUnit );
		emit( parsed.text, nextUnit );
	};

	const options = rules.allowAuto ? [ ...UNITS, AUTO ] : UNITS;

	return (
		<div className={ unit === AUTO ? 'fb-unit-input is-auto' : 'fb-unit-input' }>
			<input
				ref={ input }
				className="fb-unit-input__number"
				type="text"
				inputMode="decimal"
				aria-label={ label }
				value={ unit === AUTO ? '' : text }
				placeholder={ unit === AUTO ? AUTO : textOf( placeholder ) }
				onChange={ ( event ) => onType( event.target.value ) }
			/>
			<select
				className="fb-unit-input__unit"
				aria-label={ __( 'Unit', 'fb-editor' ) }
				value={ unit }
				onChange={ ( event ) => {
					setUnit( event.target.value );
					emit( text, event.target.value );
				} }
			>
				{ options.map( ( option ) => (
					<option key={ option } value={ option }>
						{ option }
					</option>
				) ) }
			</select>
		</div>
	);
}
