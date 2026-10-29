/**
 * Mirrors Fb\Blocks\Values\BlockAttribute: a block's whole `fb` attribute,
 * the variant it uses (kept even if it doesn't exist) and its own values;
 * plus its actions (anchor and rules, see BlockActions), stored alongside
 * but no part of its look.
 *
 *   {"variant": "primary", "looks": […], "anchor": "a7k2b9", "actions": […]}
 */
import type { ActionCatalog } from '../../Actions/action-catalog';
import { BlockActions } from '../../Actions/block-actions';
import type { BreakpointList } from '../../Breakpoints/breakpoints';
import { JsonValue } from '../../Json/json';
import type { BlockDefinition } from '../Definition/block-definition';
import { parseVariantSlug, type VariantSlug } from '../Variants/variant-set';
import { StatefulValues } from './stateful-values';

export class BlockAttribute {
	constructor(
		public readonly variant: VariantSlug | null,
		public readonly values: StatefulValues,
		public readonly actions: BlockActions = BlockActions.NONE
	) {}

	/**
	 * `actions`: what rules are parsed against. Pass the editor's catalog
	 * wherever the attribute may be written back (ActionCatalog.EMPTY drops
	 * every rule): only places that just read values may skip it.
	 */
	static fromJson( json: unknown, definition: BlockDefinition, breakpoints: BreakpointList, actions: ActionCatalog ): BlockAttribute {
		return new BlockAttribute( BlockAttribute.variantOf( json ), StatefulValues.fromJson( json, definition, breakpoints ), BlockActions.fromAttribute( json, actions ) );
	}

	/** Just the variant, without parsing values (cheap, for classes). */
	static variantOf( json: unknown ): VariantSlug | null {
		const root = JsonValue.of( json, 'fb' );
		return root.isObject() && root.has( 'variant' ) ? parseVariantSlug( root.get( 'variant' ).decoded() ) : null;
	}

	withValues( values: StatefulValues ): BlockAttribute {
		return new BlockAttribute( this.variant, values, this.actions );
	}

	withVariant( variant: VariantSlug | null ): BlockAttribute {
		return new BlockAttribute( variant, this.values, this.actions );
	}

	withActions( actions: BlockActions ): BlockAttribute {
		return new BlockAttribute( this.variant, this.values, actions );
	}

	/** Plain JSON for setAttributes(). */
	toAttribute(): unknown {
		return JSON.parse( JSON.stringify( this ) ) as unknown;
	}

	/** Canonical form, as PHP serializes it (its actions after its look). */
	toJSON(): Record< string, unknown > {
		const values = this.values.toJSON();
		return { ...( this.variant === null ? {} : { variant: this.variant } ), ...values, ...this.actions.toJSON() };
	}
}
