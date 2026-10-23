/**
 * Mirrors Fb\Values\CssValue and Fb\Values\ResponsiveValue.
 */
import type { PresetVariables } from '../Presets/presets';

/** Renders to a safe CSS value. Presets need the variable lookup. */
export interface CssValue {
	toCss( presets: PresetVariables ): string;
}

/** A value that can be set per breakpoint (desktop-first inheritance). */
export interface ResponsiveValue {
	/** This value, with anything it leaves unset taken from `larger`. */
	inheritFrom( larger: ResponsiveValue ): ResponsiveValue;
	toJSON(): unknown;
}

export function isCssValue( value: unknown ): value is CssValue & ResponsiveValue {
	return typeof value === 'object' && value !== null && 'toCss' in value;
}
