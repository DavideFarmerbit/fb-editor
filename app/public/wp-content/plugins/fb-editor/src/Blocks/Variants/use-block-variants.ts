/**
 * A block type's variants in the editor: theme variants (from settings)
 * with the saved variants post laid on top, by slug.
 */
import { useMemo } from '@wordpress/element';

import { settings } from '../../Editor/settings';
import type { BlockDefinition } from '../Definition/block-definition';
import { useDesignPost } from '../Storage/use-design-post';
import { POST_TYPE } from './BlockVariantsPostType.generated';
import { VariantSet } from './variant-set';

export class BlockVariants {
	constructor(
		/** Theme + saved: what blocks pick from. */
		public readonly all: VariantSet,
		/** Saved only: what gets edited. */
		public readonly saved: VariantSet,
		/** The theme's (fb.json), read-only. */
		public readonly theme: VariantSet,
		/** Whether the user may change variants (edit_theme_options). */
		public readonly canEdit: boolean
	) {}
}

export function useBlockVariants( definition: BlockDefinition ): { variants: BlockVariants; save: ( saved: VariantSet ) => void } {
	const info = settings.designFor( definition );
	const post = useDesignPost( definition, POST_TYPE, info.postSlug, '[]' );

	const variants = useMemo( () => {
		const saved = VariantSet.fromJson( post.content, definition, settings.breakpoints );
		return new BlockVariants( info.themeVariants.overlay( saved ), saved, info.themeVariants, post.canEdit );
	}, [ post.content, post.canEdit, definition, info ] );

	return { variants, save: ( saved: VariantSet ) => post.save( saved.toAttribute() ) };
}
