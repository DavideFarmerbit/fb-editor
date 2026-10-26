/**
 * What a control shows at the inspected breakpoint, in fallback order:
 *   1. its own value here,
 *   2. inherited from a larger breakpoint of this block,
 *   3. the fallbacks, in order (see Fallbacks).
 * Every field reads through this, so the chain is the same everywhere.
 */
import { __, sprintf } from '@wordpress/i18n';

import type { BreakpointId } from '../../Breakpoints/breakpoints';
import type { ResponsiveValue } from '../../Values/value';
import { Scope } from '../Definition/block-definition';
import type { ControlFieldProps } from './control-field';

export class ControlReading {
	constructor(
		/** Where this control's value lives (the base, for static controls). */
		public readonly at: BreakpointId,
		public readonly own: ResponsiveValue | null,
		/** What applies when there's no own value; null when nothing does. */
		public readonly inherited: ResponsiveValue | null,
		/** e.g. "Inherited from XL", "From Section default"; null when own. */
		public readonly status: string | null
	) {}

	/** The value in effect: own, else inherited. */
	get shown(): ResponsiveValue | null {
		return this.own ?? this.inherited;
	}
}

/** "Inherited from XL". */
export function inheritedFrom( label: string ): string {
	/* translators: %s: breakpoint label */
	return sprintf( __( 'Inherited from %s', 'fb-editor' ), label );
}

/** "From Section default". */
export function fromDefault( blockTitle: string ): string {
	/* translators: %s: block type name */
	return sprintf( __( 'From %s default', 'fb-editor' ), blockTitle );
}

export function readControl( { control, values, fallbacks, breakpoint, breakpoints }: ControlFieldProps ): ControlReading {
	const at = control.scope === Scope.Static ? breakpoints.base.id : breakpoint.id;
	const responsive = values.get( control.id );

	const own = responsive.at( at );
	if ( own !== null ) {
		return new ControlReading( at, own, null, null );
	}

	const fromBlock = responsive.resolve( at, breakpoints );
	if ( fromBlock !== null ) {
		const source = breakpoints.get( responsive.source( at, breakpoints ) ?? at );
		return new ControlReading( at, null, fromBlock, source === undefined ? null : inheritedFrom( source.label ) );
	}

	const fromFallback = fallbacks.resolve( control.id, at, breakpoints );
	return new ControlReading( at, null, fromFallback?.value ?? null, fromFallback?.status ?? null );
}
