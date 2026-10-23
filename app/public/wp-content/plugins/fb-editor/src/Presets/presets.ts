/**
 * Preset variable lookup. Variable names are computed by PHP (core's
 * kebab-casing) and handed over in the editor settings, never re-derived.
 */
import type { JsonValue } from '../Json/json';
import { PresetKind } from './PresetKind.generated';

export { PresetKind };

export class PresetVariable {
	constructor(
		public readonly kind: PresetKind,
		public readonly slug: string,
		/** e.g. "--wp--preset--spacing--2-xl" */
		public readonly variable: string
	) {}

	static fromJson( json: JsonValue ): PresetVariable {
		return new PresetVariable( json.get( 'kind' ).oneOf( PresetKind ), json.get( 'slug' ).string(), json.get( 'variable' ).string() );
	}
}

/**
 * Lookup of preset slug → CSS custom property name.
 */
export class PresetVariables {
	private readonly byKey: ReadonlyMap< string, string >;

	constructor( presets: readonly PresetVariable[] ) {
		this.byKey = new Map( presets.map( ( preset ) => [ `${ preset.kind }|${ preset.slug }`, preset.variable ] ) );
	}

	variable( kind: PresetKind, slug: string ): string {
		// Unknown slugs (a preset removed from the theme) still produce a
		// reference, so the CSS stays valid and falls back like core's.
		return this.byKey.get( `${ kind }|${ slug }` ) ?? `--wp--preset--${ kind }--${ slug }`;
	}
}
