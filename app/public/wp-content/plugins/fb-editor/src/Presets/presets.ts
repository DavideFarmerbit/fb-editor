/**
 * Mirrors Fb\Presets\Preset and the theme's preset list, as sent in the
 * editor settings. Variable names are computed by PHP (core's kebab-casing)
 * and never re-derived here.
 */
import type { JsonValue } from '../Json/json';
import { PresetKind } from './PresetKind.generated';

export { PresetKind };

export class Preset {
	constructor(
		public readonly kind: PresetKind,
		public readonly slug: string,
		public readonly name: string,
		/** e.g. "1rem" */
		public readonly value: string,
		/** e.g. "--wp--preset--spacing--40" */
		public readonly variable: string
	) {}

	static fromJson( json: JsonValue ): Preset {
		return new Preset(
			json.get( 'kind' ).oneOf( PresetKind ),
			json.get( 'slug' ).string(),
			json.get( 'name' ).string(),
			json.get( 'value' ).string(),
			json.get( 'variable' ).string()
		);
	}
}

/**
 * The theme's presets, in theme order.
 */
export class Presets {
	constructor( private readonly presets: readonly Preset[] ) {}

	ofKind( kind: PresetKind ): readonly Preset[] {
		return this.presets.filter( ( preset ) => preset.kind === kind );
	}

	find( kind: PresetKind, slug: string ): Preset | undefined {
		return this.presets.find( ( preset ) => preset.kind === kind && preset.slug === slug );
	}

	variable( kind: PresetKind, slug: string ): string {
		// Unknown slugs (a preset removed from the theme) still produce a
		// reference, so the CSS stays valid and falls back like core's.
		return this.find( kind, slug )?.variable ?? `--wp--preset--${ kind }--${ slug }`;
	}
}
