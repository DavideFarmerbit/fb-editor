/**
 * Mirrors Fb\Blocks\Values\BlockAttribute: a block's whole `fb` attribute,
 * the variant it uses (kept even if it doesn't exist) and its own values.
 *
 *   {"variant": "primary", "base": {…}, "states": {…}}
 */
import type { BreakpointList } from '../../Breakpoints/breakpoints';
import { JsonValue } from '../../Json/json';
import type { BlockDefinition } from '../Definition/block-definition';
import { parseVariantSlug, type VariantSlug } from '../Variants/variant-set';
import { StatefulValues } from './stateful-values';

export class BlockAttribute {
	constructor(
		public readonly variant: VariantSlug | null,
		public readonly values: StatefulValues
	) {}

	static fromJson( json: unknown, definition: BlockDefinition, breakpoints: BreakpointList ): BlockAttribute {
		return new BlockAttribute( BlockAttribute.variantOf( json ), StatefulValues.fromJson( json, definition, breakpoints ) );
	}

	/** Just the variant, without parsing values (cheap, for classes). */
	static variantOf( json: unknown ): VariantSlug | null {
		const root = JsonValue.of( json, 'fb' );
		return root.isObject() && root.has( 'variant' ) ? parseVariantSlug( root.get( 'variant' ).decoded() ) : null;
	}

	withValues( values: StatefulValues ): BlockAttribute {
		return new BlockAttribute( this.variant, values );
	}

	withVariant( variant: VariantSlug | null ): BlockAttribute {
		return new BlockAttribute( variant, this.values );
	}

	/** Plain JSON for setAttributes(). */
	toAttribute(): unknown {
		return JSON.parse( JSON.stringify( this ) ) as unknown;
	}

	/** Canonical form, as PHP serializes it. */
	toJSON(): Record< string, unknown > {
		const values = this.values.toJSON();
		return this.variant === null ? values : { variant: this.variant, ...values };
	}
}
