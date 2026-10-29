/**
 * One parameter of a step, edited with the UI its input calls for. Values
 * are plain (no breakpoints, no inheritance): unset means the runner's
 * default, which the select and toggle show.
 */
import { Button, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { external } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { ClassNameInput } from '../../Blocks/Definition/Input/ClassName/class-name-input';
import { LengthInput } from '../../Blocks/Definition/Input/Length/length-input';
import { UnitInput } from '../../Blocks/Definition/Input/Length/unit-input';
import { isAllowedUrl, LinkInput } from '../../Blocks/Definition/Input/Link/link-input';
import { SelectInput } from '../../Blocks/Definition/Input/Select/select-input';
import { TargetInput, TargetValue } from '../../Blocks/Definition/Input/Target/target-input';
import { TextInput } from '../../Blocks/Definition/Input/Text/text-input';
import { ToggleInput } from '../../Blocks/Definition/Input/Toggle/toggle-input';
import { PATTERN as KEYWORD_PATTERN } from '../../Values/KeywordValue.generated';
import { BooleanValue } from '../../Values/boolean';
import { LinkValue, TextValue } from '../../Values/content';
import { KeywordValue } from '../../Values/keyword';
import { AutoLength, CustomLength } from '../../Values/length';
import type { ResponsiveValue } from '../../Values/value';
import type { Param } from '../action-catalog';
import { TargetField } from './target-field';

interface ParamFieldProps {
	readonly param: Param;
	readonly value: ResponsiveValue | null;
	/** The block the rule is on (targets: "this block"). */
	readonly clientId: string;
	readonly onChange: ( value: ResponsiveValue | null ) => void;
}

/** A required parameter's label says so while it's missing. */
function labelOf( param: Param, value: ResponsiveValue | null ): string {
	/* translators: %s: parameter label */
	return param.required && value === null ? sprintf( __( '%s (required)', 'fb-editor' ), param.label ) : param.label;
}

/** A text box keeping what's typed even while it isn't valid yet. */
function DraftText( { label, value, placeholder, valid, onValid }: { label: string; value: string; placeholder: string; valid: ( text: string ) => boolean; onValid: ( text: string | null ) => void } ): ReactElement {
	const [ draft, setDraft ] = useState< string | null >( null );
	const text = draft ?? value;
	return (
		<TextControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			placeholder={ placeholder }
			className={ draft !== null && draft.trim() !== '' && ! valid( draft.trim() ) ? 'is-invalid' : '' }
			value={ text }
			onChange={ ( typed: string ) => {
				setDraft( typed );
				if ( typed.trim() === '' ) {
					onValid( null );
				} else if ( valid( typed.trim() ) ) {
					onValid( typed.trim() );
				}
			} }
			onBlur={ () => setDraft( null ) }
		/>
	);
}

export function ParamField( { param, value, clientId, onChange }: ParamFieldProps ): ReactElement {
	const { input } = param;
	const label = labelOf( param, value );

	if ( input instanceof TargetInput ) {
		return <TargetField label={ label } value={ value instanceof TargetValue && ! value.isSelf() ? value : null } clientId={ clientId } onChange={ onChange } />;
	}

	if ( input instanceof SelectInput ) {
		const fallback = input.options.find( ( option ) => option.value === input.fallback );
		return (
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				value={ value instanceof KeywordValue ? value.keyword : '' }
				options={ [
					/* translators: %s: default option label */
					{ value: '', label: fallback === undefined ? __( 'Not set', 'fb-editor' ) : sprintf( __( 'Default (%s)', 'fb-editor' ), fallback.label ) },
					...input.options.map( ( option ) => ( { value: option.value, label: option.label } ) ),
				] }
				onChange={ ( chosen: string ) => onChange( chosen === '' ? null : new KeywordValue( chosen ) ) }
			/>
		);
	}

	if ( input instanceof ToggleInput ) {
		// Stored only when it differs from what applies unset.
		return (
			<ToggleControl
				__nextHasNoMarginBottom
				label={ label }
				checked={ value instanceof BooleanValue ? value.on : input.fallback }
				onChange={ ( on: boolean ) => onChange( on === input.fallback ? null : new BooleanValue( on ) ) }
			/>
		);
	}

	if ( input instanceof ClassNameInput ) {
		return (
			<DraftText
				label={ label }
				placeholder="is-active"
				value={ value instanceof KeywordValue ? value.keyword : '' }
				valid={ ( text ) => KEYWORD_PATTERN.test( text ) }
				onValid={ ( text ) => onChange( text === null ? null : new KeywordValue( text ) ) }
			/>
		);
	}

	if ( input instanceof TextInput ) {
		return (
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				maxLength={ input.maxLength }
				value={ value instanceof TextValue ? value.text : '' }
				onChange={ ( text: string ) => onChange( text.trim() === '' ? null : new TextValue( text ) ) }
			/>
		);
	}

	if ( input instanceof LinkInput ) {
		const link = value instanceof LinkValue ? value : null;
		return (
			<div className="fb-param-link">
				<DraftText
					label={ label }
					placeholder="https://"
					value={ link?.url ?? '' }
					valid={ isAllowedUrl }
					onValid={ ( url ) => onChange( url === null ? null : new LinkValue( url, link?.newTab ?? false, link?.nofollow ?? false ) ) }
				/>
				<Button
					size="small"
					icon={ external }
					label={ __( 'Open in a new tab', 'fb-editor' ) }
					isPressed={ link?.newTab === true }
					disabled={ link === null }
					accessibleWhenDisabled
					onClick={ () => link !== null && onChange( new LinkValue( link.url, ! link.newTab, link.nofollow ) ) }
				/>
			</div>
		);
	}

	if ( input instanceof LengthInput ) {
		return (
			<div className="fb-param-length">
				<span className="fb-param-length__label">{ label }</span>
				<UnitInput
					label={ label }
					value={ value instanceof CustomLength || value instanceof AutoLength ? value : null }
					placeholder={ null }
					rules={ input }
					onChange={ onChange }
				/>
			</div>
		);
	}

	/* translators: %s: parameter label */
	return <p className="fb-param-unsupported">{ sprintf( __( '%s: not editable here', 'fb-editor' ), param.label ) }</p>;
}
