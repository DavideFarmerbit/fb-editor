/**
 * Mirrors Fb\Blocks\Storage\BlockDesignInfo: one block type's theme design
 * (defaults and variants, read-only) and the slug of its saved posts.
 */
import type { StatefulValues } from '../Values/stateful-values';
import type { VariantSet } from '../Variants/variant-set';

export class BlockDesignInfo {
	constructor(
		/** e.g. "fb/section" */
		public readonly block: string,
		/** Slug of the type's design posts for the active theme, from PHP. */
		public readonly postSlug: string,
		public readonly themeDefaults: StatefulValues,
		public readonly themeVariants: VariantSet
	) {}
}
