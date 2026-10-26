/**
 * What every block with fb controls gets in the editor, whether an fb block
 * or a managed core block: parsed values, its variant, type defaults, the
 * generated sidebar, and live CSS (defaults + variant + instance) in the
 * canvas.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import type { ReactElement } from 'react';

import { settings } from '../Editor/settings';
import { store } from '../Editor/store';
import { ATTRIBUTE } from './BlockRegistry.generated';
import { DefaultsStyles } from './Defaults/defaults-styles';
import { useBlockDefaults, type BlockDefaults } from './Defaults/use-block-defaults';
import type { BlockDefinition, Control } from './Definition/block-definition';
import type { BlockState } from './States/BlockState.generated';
import { fromDefault } from './Inspector/control-reading';
import { WeakerSource } from './Inspector/fallbacks';
import { StatefulInspector } from './Inspector/stateful-inspector';
import { InstanceStyles } from './Rendering/instance-styles';
import { BlockAttribute } from './Values/block-attribute';
import type { StatefulValues } from './Values/stateful-values';
import { useBlockVariants, type BlockVariants } from './Variants/use-block-variants';
import type { Variant, VariantSlug } from './Variants/variant-set';
import { VariantStyles } from './Variants/variant-styles';

export class FbBlockState {
	constructor(
		public readonly attribute: BlockAttribute,
		/** The variant the block uses, if it exists. */
		public readonly variant: Variant | null,
		public readonly defaults: BlockDefaults,
		public readonly variants: BlockVariants,
		/** Editor-only class on the block root; never saved. */
		public readonly instanceClass: string,
		public readonly setAttribute: ( attribute: BlockAttribute ) => void,
		public readonly saveDefaults: ( saved: StatefulValues ) => void
	) {}

	/** The block's own values. */
	get values(): StatefulValues {
		return this.attribute.values;
	}

	setValues( values: StatefulValues ): void {
		this.setAttribute( this.attribute.withValues( values ) );
	}

	setVariant( variant: VariantSlug | null ): void {
		this.setAttribute( this.attribute.withVariant( variant ) );
	}

	/** What the block's own values fall back to, strongest first. */
	weaker(): StatefulValues[] {
		return this.variant === null ? [ this.defaults.values ] : [ this.variant.values, this.defaults.values ];
	}
}

/**
 * Editor only: the class that previews the state inspected in this block's
 * sidebar (memory key: its clientId), while the block is selected.
 */
export function usePreviewClass( definition: BlockDefinition, clientId: string, isSelected: boolean ): string | null {
	const inspected = useSelect( ( select ) => select( store ).getInspectedState( clientId ), [ clientId ] );
	if ( ! isSelected || inspected === null ) {
		return null;
	}
	return definition.states.find( ( state ) => state.id === inspected )?.previewClass ?? null;
}

/** The variant class, when the block's variant exists (as on the frontend). */
export function variantClass( definition: BlockDefinition, variant: Variant | null ): string | null {
	return variant === null ? null : definition.variantClass( variant.slug );
}

/** The variant a raw `fb` attribute points to, if it exists. */
export function useVariantOf( definition: BlockDefinition, attribute: unknown ): Variant | null {
	const { variants } = useBlockVariants( definition );
	const slug = BlockAttribute.variantOf( attribute );
	return slug === null ? null : variants.all.get( slug );
}

/**
 * Editor-only instance class: stable per block while editing (the frontend
 * hash depends on values, so it would change on every edit).
 */
export function editorInstanceClass( clientId: string ): string {
	return `fb-i-${ clientId }`;
}

/** "From Primary variant". */
function fromVariant( name: string ): string {
	/* translators: %s: variant name */
	return sprintf( __( 'From %s variant', 'fb-editor' ), name );
}

export function useFbBlock(
	definition: BlockDefinition,
	attributes: Readonly< Record< string, unknown > >,
	setAttributes: ( attributes: Record< string, unknown > ) => void,
	clientId: string
): FbBlockState {
	const raw = attributes[ ATTRIBUTE ];
	const attribute = useMemo( () => BlockAttribute.fromJson( raw, definition, settings.breakpoints ), [ raw, definition ] );
	const { defaults, save } = useBlockDefaults( definition );
	const { variants } = useBlockVariants( definition );

	return new FbBlockState(
		attribute,
		attribute.variant === null ? null : variants.all.get( attribute.variant ),
		defaults,
		variants,
		editorInstanceClass( clientId ),
		( next ) => setAttributes( { [ ATTRIBUTE ]: next.toAttribute() } ),
		save
	);
}

interface FbBlockChromeProps {
	readonly definition: BlockDefinition;
	readonly state: FbBlockState;
	readonly clientId: string;
	/** The block root in the canvas, once mounted (where CSS is written). */
	readonly root: HTMLElement | null;
}

export function FbBlockChrome( { definition, state, clientId, root }: FbBlockChromeProps ): ReactElement {
	const { values, defaults, variant } = state;
	const defaultsSource = new WeakerSource( defaults.values, fromDefault( definition.title ) );

	return (
		<>
			<InspectorControls>
				<StatefulInspector
					definition={ definition }
					values={ values }
					weaker={ variant === null ? [ defaultsSource ] : [ new WeakerSource( variant.values, fromVariant( variant.name ) ), defaultsSource ] }
					listFallbackControls={ false }
					memoryKey={ clientId }
					onChange={ ( next ) => state.setValues( next ) }
					makeDefault={
						defaults.canEdit
							? ( control: Control, look: BlockState | null ) =>
									state.saveDefaults(
										defaults.saved.withLook( look, defaults.saved.look( look ).withControlFrom( control, values.look( look ) ) )
									)
							: null
					}
				/>
			</InspectorControls>
			<DefaultsStyles definition={ definition } defaults={ defaults.values } root={ root } />
			{ variant !== null && <VariantStyles definition={ definition } variant={ variant } defaults={ defaults.values } root={ root } /> }
			<InstanceStyles definition={ definition } values={ values } weaker={ state.weaker() } instanceClass={ state.instanceClass } root={ root } />
		</>
	);
}
