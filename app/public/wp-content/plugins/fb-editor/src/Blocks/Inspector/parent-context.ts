/**
 * The block's parent, as placed controls see it (see Placement): which
 * block type it is and its resolved values (own → variant → type default),
 * so e.g. flex-grow only shows inside a Container laid out as flex.
 */
import { __, sprintf } from '@wordpress/i18n';

import type { BreakpointId } from '../../Breakpoints/breakpoints';
import { settings } from '../../Editor/settings';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import type { ValueChain } from '../Values/value-chain';

export class ParentContext {
	constructor(
		public readonly definition: BlockDefinition,
		public readonly values: ValueChain
	) {}

	/** Whether a control applies here, at this breakpoint. */
	allows( control: Control, breakpoint: BreakpointId ): boolean {
		const { placement } = control;
		return placement === null || ( placement.parent === this.definition.name && placement.when.evaluate( this.values.resolvedAt( breakpoint ) ) );
	}
}

/** Why a placed control doesn't apply: "Inside a Container with Display: Flex". */
export function describePlacement( control: Control ): string | null {
	const { placement } = control;
	if ( placement === null ) {
		return null;
	}
	const parent = settings.blocks.find( ( candidate ) => candidate.name === placement.parent );
	return parent === undefined
		? placement.parent
		: /* translators: 1: block type, 2: condition, e.g. "Display: Flex" */ sprintf( __( 'inside a %1$s with %2$s', 'fb-editor' ), parent.title, placement.when.describe( parent ) );
}
