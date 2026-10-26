/**
 * Mirrors Fb\Blocks\Defaults\BlockDefaultsInfo: one block type's theme
 * defaults (read-only) and the slug of its saved-defaults post.
 */
import type { StatefulValues } from '../Values/stateful-values';

export class BlockDefaultsInfo {
	constructor(
		/** e.g. "fb/section" */
		public readonly block: string,
		/** Slug of the fb_block_default post for the active theme, from PHP. */
		public readonly postSlug: string,
		public readonly theme: StatefulValues
	) {}
}
