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

	withName( name: string ): Variant {
		return new Variant( this.slug, name, this.values );
	}

	withValues( values: StatefulValues ): Variant {
		return new Variant( this.slug, this.name, values );
	}

	toJSON(): Record< string, unknown > {
		return { slug: this.slug, name: this.name, values: this.values };
	}
}

/**
 * A new, unused slug from a name: "Big & Bold" → "big-bold", then
 * "big-bold-2"… when taken.
 */
export function slugFromName( name: string, taken: VariantSet ): VariantSlug {
	const base =
		name
			.normalize( 'NFKD' )
			// "é" → "e" + accent: drop the accent.
			.replace( /[\u0300-\u036f]/g, '' )
			.toLowerCase()
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' ) || 'variant';
	for ( let index = 1; ; index++ ) {
		const slug = parseVariantSlug( index === 1 ? base : `${ base }-${ index }` );
		if ( slug !== null && taken.get( slug ) === null ) {
			return slug;
		}
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
			const values = StatefulValues.fromJson( item.has( 'values' ) ? item.get( 'values' ).decoded() : null, definition, breakpoints, false );
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

	/** A copy with this variant added, or replacing the one with its slug. */
	with( variant: Variant ): VariantSet {
		return this.get( variant.slug ) === null
			? new VariantSet( [ ...this.variants, variant ] )
			: new VariantSet( this.variants.map( ( candidate ) => ( candidate.slug === variant.slug ? variant : candidate ) ) );
	}

	without( slug: VariantSlug ): VariantSet {
		return new VariantSet( this.variants.filter( ( variant ) => variant.slug !== slug ) );
	}

	/** Plain JSON for post content. */
	toAttribute(): unknown {
		return JSON.parse( JSON.stringify( this ) ) as unknown;
	}

	toJSON(): readonly Variant[] {
		return this.variants;
	}
}
