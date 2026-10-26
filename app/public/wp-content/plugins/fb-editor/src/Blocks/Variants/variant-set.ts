/**
 * Mirrors Fb\Blocks\Variants\{VariantSlug, Variant, VariantSet}: a block
 * type's named looks, stored as a list:
 *
 *   [{"slug": "primary", "name": "Primary", "values": {"base": …}}]
 */
import type { BreakpointList } from '../../Breakpoints/breakpoints';
import { InvalidJson, JsonValue } from '../../Json/json';
import type { BlockDefinition } from '../Definition/block-definition';
import { StatefulValues } from '../Values/stateful-values';
import { PATTERN } from './VariantSlug.generated';

/** A variant's stable id within its block type, e.g. "primary". */
export type VariantSlug = string & { readonly __brand: 'VariantSlug' };

export function parseVariantSlug( value: unknown ): VariantSlug | null {
	return typeof value === 'string' && PATTERN.test( value ) ? ( value as VariantSlug ) : null;
}

export class Variant {
	constructor(
		public readonly slug: VariantSlug,
		public readonly name: string,
		public readonly values: StatefulValues
	) {}

	toJSON(): Record< string, unknown > {
		return { slug: this.slug, name: this.name, values: this.values };
	}
}

export class VariantSet {
	private constructor( public readonly variants: readonly Variant[] ) {}

	static empty(): VariantSet {
		return new VariantSet( [] );
	}

	/** Mirrors VariantSet::fromJson(); names are sanitized again by PHP on save. */
	static fromJson( json: unknown, definition: BlockDefinition, breakpoints: BreakpointList ): VariantSet {
		let items: JsonValue[];
		try {
			items = JsonValue.of( json, 'variants' ).list();
		} catch ( error ) {
			if ( error instanceof InvalidJson ) {
				return VariantSet.empty();
			}
			throw error;
		}

		const variants = new Map< VariantSlug, Variant >();
		for ( const item of items ) {
			const slug = parseVariantSlug( item.isObject() && item.has( 'slug' ) ? item.get( 'slug' ).decoded() : null );
			if ( slug === null || variants.has( slug ) ) {
				continue;
			}
			const rawName = item.has( 'name' ) ? item.get( 'name' ).decoded() : null;
			const name = typeof rawName === 'string' ? rawName.trim() : '';
			const values = StatefulValues.fromJson( item.has( 'values' ) ? item.get( 'values' ).decoded() : null, definition, breakpoints );
			variants.set( slug, new Variant( slug, name === '' ? slug : name, values ) );
		}

		return new VariantSet( [ ...variants.values() ] );
	}

	get( slug: VariantSlug ): Variant | null {
		return this.variants.find( ( variant ) => variant.slug === slug ) ?? null;
	}

	/** Mirrors VariantSet::overlay(): saved over theme, by slug. */
	overlay( over: VariantSet ): VariantSet {
		const variants = this.variants.map( ( variant ) => {
			const saved = over.get( variant.slug );
			return saved === null ? variant : new Variant( variant.slug, saved.name, variant.values.overlay( saved.values ) );
		} );
		return new VariantSet( [ ...variants, ...over.variants.filter( ( variant ) => this.get( variant.slug ) === null ) ] );
	}

	/** Plain JSON for post content. */
	toAttribute(): unknown {
		return JSON.parse( JSON.stringify( this ) ) as unknown;
	}

	toJSON(): readonly Variant[] {
		return this.variants;
	}
}
