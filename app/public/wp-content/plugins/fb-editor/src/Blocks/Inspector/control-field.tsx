/**
 * The contract every control UI implements, and the dispatch from a
 * control's input to its UI. Each UI lives next to its PHP input class.
 */
import type { ReactElement } from 'react';

import type { Breakpoint, BreakpointList } from '../../Breakpoints/breakpoints';
import type { ResponsiveValue } from '../../Values/value';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import { ColorField } from '../Definition/Input/Color/color-control';
import { AspectRatioField } from '../Definition/Input/AspectRatio/aspect-ratio-control';
import { AspectRatioInput } from '../Definition/Input/AspectRatio/aspect-ratio-input';
import { BorderField } from '../Definition/Input/Border/border-control';
import { BorderInput } from '../Definition/Input/Border/border-input';
import { ColorInput } from '../Definition/Input/Color/color-input';
import { FocalPointField } from '../Definition/Input/FocalPoint/focal-point-control';
import { FocalPointInput } from '../Definition/Input/FocalPoint/focal-point-input';
import { GridColumnsField } from '../Definition/Input/GridColumns/grid-columns-control';
import { GridColumnsInput } from '../Definition/Input/GridColumns/grid-columns-input';
import { LengthField } from '../Definition/Input/Length/length-control';
import { LengthInput } from '../Definition/Input/Length/length-input';
import { LinkField } from '../Definition/Input/Link/link-control';
import { LinkInput } from '../Definition/Input/Link/link-input';
import { MediaField } from '../Definition/Input/Media/media-control';
import { MediaInput } from '../Definition/Input/Media/media-input';
import { SvgField } from '../Definition/Input/Svg/svg-control';
import { SvgInput } from '../Definition/Input/Svg/svg-input';
import { TextField } from '../Definition/Input/Text/text-control';
import { TextInput } from '../Definition/Input/Text/text-input';
import { NumberField } from '../Definition/Input/Number/number-control';
import { NumberInput } from '../Definition/Input/Number/number-input';
import { PresetField } from '../Definition/Input/Preset/preset-control';
import { PresetInput } from '../Definition/Input/Preset/preset-input';
import { SelectField } from '../Definition/Input/Select/select-control';
import { SelectInput } from '../Definition/Input/Select/select-input';
import { SpacingField } from '../Definition/Input/Spacing/spacing-control';
import { SpacingInput } from '../Definition/Input/Spacing/spacing-input';
import { ToggleField } from '../Definition/Input/Toggle/toggle-control';
import { ToggleInput } from '../Definition/Input/Toggle/toggle-input';
import type { BlockValues } from '../Values/block-values';
import type { Fallbacks } from './fallbacks';

export interface ControlFieldProps {
	readonly definition: BlockDefinition;
	readonly control: Control;
	/** The look being edited (a block's base or hover, a type default's…). */
	readonly values: BlockValues;
	/** What it falls back to beyond its own larger breakpoints. */
	readonly fallbacks: Fallbacks;
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
	if ( input instanceof NumberInput ) {
		return <NumberField { ...props } input={ input } />;
	}
	if ( input instanceof GridColumnsInput ) {
		return <GridColumnsField { ...props } input={ input } />;
	}
	if ( input instanceof AspectRatioInput ) {
		return <AspectRatioField { ...props } input={ input } />;
	}
	if ( input instanceof TextInput ) {
		return <TextField { ...props } input={ input } />;
	}
	if ( input instanceof LinkInput ) {
		return <LinkField { ...props } input={ input } />;
	}
	if ( input instanceof MediaInput ) {
		return <MediaField { ...props } input={ input } />;
	}
	if ( input instanceof SvgInput ) {
		return <SvgField { ...props } input={ input } />;
	}
	if ( input instanceof FocalPointInput ) {
		return <FocalPointField { ...props } input={ input } />;
	}
	if ( input instanceof BorderInput ) {
		return <BorderField { ...props } input={ input } />;
	}

	return input satisfies never;
}
