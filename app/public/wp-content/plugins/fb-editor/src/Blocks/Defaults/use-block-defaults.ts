/**
 * A block type's defaults in the editor: theme defaults (from settings)
 * with the saved defaults post laid on top.
 */
import { useMemo } from '@wordpress/element';

import { settings } from '../../Editor/settings';
import type { BlockDefinition } from '../Definition/block-definition';
import { useDesignPost } from '../Storage/use-design-post';
import { StatefulValues } from '../Values/stateful-values';
import { POST_TYPE } from './BlockDefaultsPostType.generated';

export class BlockDefaults {
	constructor(
		/** Theme + saved: what unset values fall back to. */
		public readonly values: StatefulValues,
		/** Saved only: what "Make default" and the defaults sidebar edit. */
		public readonly saved: StatefulValues,
		/** The theme's defaults (fb.json), read-only: what saved ones overlay. */
		public readonly theme: StatefulValues,
		/** Whether the user may change defaults (edit_theme_options). */
		public readonly canEdit: boolean
	) {}
}

export function useBlockDefaults( definition: BlockDefinition ): { defaults: BlockDefaults; save: ( saved: StatefulValues ) => void } {
	const info = settings.designFor( definition );
	const post = useDesignPost( definition, POST_TYPE, info.postSlug, '{}' );

	const defaults = useMemo( () => {
		const saved = StatefulValues.fromJson( post.content, definition, settings.breakpoints );
		return new BlockDefaults( info.themeDefaults.overlay( saved ), saved, info.themeDefaults, post.canEdit );
	}, [ post.content, post.canEdit, definition, info ] );

	return { defaults, save: ( saved: StatefulValues ) => post.save( saved.toAttribute() ) };
}
