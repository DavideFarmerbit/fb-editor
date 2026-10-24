/**
 * The contract every control UI implements, and the dispatch from a
 * control's input to its UI. Each UI lives next to its PHP input class.
 */
import type { ReactElement } from 'react';

import type { Breakpoint, BreakpointList } from '../../Breakpoints/breakpoints';
import type { ResponsiveValue } from '../../Values/value';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import { SelectInput, SpacingInput } from '../Definition/Input/inputs';
import { SelectField } from '../Definition/Input/select-control';
import { SpacingField } from '../Definition/Input/spacing-control';
import type { BlockValues } from '../Values/block-values';

export interface ControlFieldProps {
	readonly definition: BlockDefinition;
	readonly control: Control;
	readonly values: BlockValues;
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

	return input satisfies never;
}
