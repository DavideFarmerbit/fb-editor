/**
 * What the variant menu does, as pure functions: every action returns the
 * block's new attribute and, when variants change, the new saved set.
 *
 * "Saving" a variant always takes what the block looks like (its variant,
 * if any, with its own values on top) and leaves the block pointing at the
 * variant with no values of its own: what it showed is now the variant.
 */
import type { BreakpointList } from '../../Breakpoints/breakpoints';
import type { BlockDefinition } from '../Definition/block-definition';
import { BlockAttribute } from '../Values/block-attribute';
import { StatefulValues } from '../Values/stateful-values';
import { slugFromName, Variant, type VariantSet } from './variant-set';

export class VariantChange {
	constructor(
		public readonly attribute: BlockAttribute,
		/** The new saved variants; null when they don't change. */
		public readonly saved: VariantSet | null
	) {}
}

/** The block's look: its variant's values with its own on top. */
function lookOf( attribute: BlockAttribute, variant: Variant | null ): StatefulValues {
	return variant === null ? attribute.values : variant.values.overlay( attribute.values );
}

export function saveAsNewVariant(
	definition: BlockDefinition,
	breakpoints: BreakpointList,
	attribute: BlockAttribute,
	variant: Variant | null,
	all: VariantSet,
	saved: VariantSet,
	name: string
): VariantChange {
	const slug = slugFromName( name, all );
	return new VariantChange(
		new BlockAttribute( slug, StatefulValues.empty( definition, breakpoints ) ),
		saved.with( new Variant( slug, name.trim() === '' ? slug : name.trim(), lookOf( attribute, variant ) ) )
	);
}

/**
 * The block's own values go into its variant. Only the saved part is
 * written: a theme variant gets a saved override on top of the theme's.
 */
export function updateVariant(
	definition: BlockDefinition,
	breakpoints: BreakpointList,
	attribute: BlockAttribute,
	variant: Variant,
	saved: VariantSet
): VariantChange {
	const current = saved.get( variant.slug ) ?? new Variant( variant.slug, variant.name, StatefulValues.empty( definition, breakpoints ) );
	return new VariantChange(
		new BlockAttribute( variant.slug, StatefulValues.empty( definition, breakpoints ) ),
		saved.with( current.withValues( current.values.overlay( attribute.values ) ) )
	);
}

/** The variant's values become the block's own; the link is dropped. */
export function materialize( attribute: BlockAttribute, variant: Variant ): VariantChange {
	return new VariantChange( new BlockAttribute( null, lookOf( attribute, variant ) ), null );
}
