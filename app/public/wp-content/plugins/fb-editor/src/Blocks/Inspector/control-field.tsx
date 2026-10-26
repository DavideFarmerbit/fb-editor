/**
 * The contract every control UI implements, and the dispatch from a
 * control's input to its UI. Each UI lives next to its PHP input class.
 */
import type { ReactElement } from 'react';

import type { Breakpoint, BreakpointList } from '../../Breakpoints/breakpoints';
import type { ResponsiveValue } from '../../Values/value';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import { ColorField } from '../Definition/Input/Color/color-control';
import { ColorInput } from '../Definition/Input/Color/color-input';
import { LengthField } from '../Definition/Input/Length/length-control';
import { LengthInput } from '../Definition/Input/Length/length-input';
import { PresetField } from '../Definition/Input/Preset/preset-control';
import { PresetInput } from '../Definition/Input/Preset/preset-input';
import { SelectField } from '../Definition/Input/Select/select-control';
import { SelectInput } from '../Definition/Input/Select/select-input';
import { SpacingField } from '../Definition/Input/Spacing/spacing-control';
import { SpacingInput } from '../Definition/Input/Spacing/spacing-input';
import { ToggleField } from '../Definition/Input/Toggle/toggle-control';
import { ToggleInput } from '../Definition/Input/Toggle/toggle-input';
import type { BlockValues } from '../Values/block-values';

export interface ControlFieldProps {
	readonly definition: BlockDefinition;
	readonly control: Control;
	readonly values: BlockValues;
	/**
	 * The last fallback of every control: the type's defaults when editing
	 * a block, the theme's defaults when editing type defaults.
	 */
	readonly fallback: BlockValues;
	/** How a value from `fallback` is labelled, e.g. "From Section default". */
	readonly fallbackStatus: string;
	/** The breakpoint being edited (the top-bar selection). */
	readonly breakpoint: Breakpoint;
	readonly breakpoints: BreakpointList;
	/** Sets (or clears, with null) this control's value at the breakpoint. */
	readonly onChange: ( value: ResponsiveValue | null ) => void;
}

export function ControlField( props: ControlFieldProps ): ReactElement {
	const { input } = props.control;

	if ( input instanceof SelectInput ) {
		return <SelectField { ...props } input={ input } />;
	}
	if ( input instanceof SpacingInput ) {
		return <SpacingField { ...props } input={ input } />;
	}
	if ( input instanceof LengthInput ) {
		return <LengthField { ...props } input={ input } />;
	}
	if ( input instanceof ColorInput ) {
		return <ColorField { ...props } input={ input } />;
	}
	if ( input instanceof ToggleInput ) {
		return <ToggleField { ...props } input={ input } />;
	}
	if ( input instanceof PresetInput ) {
		return <PresetField { ...props } input={ input } />;
	}

	return input satisfies never;
}
