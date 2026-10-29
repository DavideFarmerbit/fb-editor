/**
 * What every block with fb controls gets in the editor, whether an fb block
 * or a managed core block: parsed values, its variant, type defaults, the
 * generated sidebar, and live CSS (defaults + variant + instance) in the
 * canvas.
 *
 * One list (core's inspector tabs are off), narrowed by the Settings /
 * Styles switches next to "Add setting" (each control declares its
 * ControlKind).
 */
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import type { ReactElement } from 'react';

import { ActionCatalog } from '../Actions/Catalog/action-catalog';
import { ActionsPanel } from '../Actions/Panel/actions-panel';
import { settings } from '../Editor/settings';
import { store } from '../Editor/store';
import { ATTRIBUTE } from './BlockRegistry.generated';
import { DefaultsStyles } from './Defaults/defaults-styles';
import { useBlockDefaults, type BlockDefaults } from './Defaults/use-block-defaults';
import type { BlockDefinition, Control } from './Definition/block-definition';
import { LookKey } from './Looks/look-key';
import { fromDefault } from './Inspector/control-reading';
import { WeakerSource } from './Inspector/fallbacks';
import { ParentContext } from './Inspector/parent-context';
import { StatefulInspector } from './Inspector/stateful-inspector';
import { PartOutline } from './Parts/part-outline';
import { InstanceStyles } from './Rendering/instance-styles';
import { BlockAttribute } from './Values/block-attribute';
import type { StatefulValues } from './Values/stateful-values';
import { useBlockVariants, type BlockVariants } from './Variants/use-block-variants';
import type { Variant, VariantSet, VariantSlug } from './Variants/variant-set';
import { VariantPicker } from './Variants/variant-picker';
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
		public readonly saveDefaults: ( saved: StatefulValues ) => void,
		/** Replaces the type's saved variants (an unsaved entity edit). */
		public readonly saveVariants: ( saved: VariantSet ) => void
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
 * Editor only: the classes that preview the states of the look inspected
 * in this block's sidebar (memory key: its clientId), while the block is
 * selected.
 */
export function usePreviewClass( definition: BlockDefinition, clientId: string, isSelected: boolean ): string | null {
	const inspected = useSelect( ( select ) => select( store ).getInspectedLook( clientId ), [ clientId ] );
	if ( ! isSelected ) {
		return null;
	}
	const classes = LookKey.fromId( inspected ).states.flatMap( ( state ) => definition.states.find( ( info ) => info.id === state )?.previewClass ?? [] );
	return classes.length === 0 ? null : classes.join( ' ' );
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

/** The block editor store, by name: its package ships no store types. */
const BLOCK_EDITOR = 'core/block-editor';

interface BlockEditorSelectors {
	getBlockRootClientId( clientId: string ): string | null;
	getBlockName( clientId: string ): string | null;
	getBlockAttributes( clientId: string ): Readonly< Record< string, unknown > > | null;
}

/**
 * The block's parent as placed controls see it. Two plain selections (a
 * name, the stored attribute), never a fresh object: useSelect compares
 * results by identity, and a new object each time would re-render on every
 * store change.
 */
function useParentContext( clientId: string, fallback: BlockDefinition ): ParentContext | null {
	const name = useSelect(
		( select ) => {
			const editor = select( BLOCK_EDITOR ) as BlockEditorSelectors;
			const root = editor.getBlockRootClientId( clientId );
			return root === null ? null : editor.getBlockName( root );
		},
		[ clientId ]
	);
	const attribute = useSelect(
		( select ) => {
			const editor = select( BLOCK_EDITOR ) as BlockEditorSelectors;
			const root = editor.getBlockRootClientId( clientId );
			return root === null ? undefined : editor.getBlockAttributes( root )?.[ ATTRIBUTE ];
		},
		[ clientId ]
	);
	const parent = name === null ? undefined : settings.blocks.find( ( candidate ) => candidate.name === name );
	// Hooks run unconditionally: without an fb parent their result is unused.
	const { defaults } = useBlockDefaults( parent ?? fallback );
	const { variants } = useBlockVariants( parent ?? fallback );

	return useMemo( () => {
		if ( parent === undefined ) {
			return null;
		}
		// Read only: the parent's actions aren't needed.
		const parsed = BlockAttribute.fromJson( attribute, parent, settings.breakpoints, ActionCatalog.EMPTY );
		const variant = parsed.variant === null ? null : variants.all.get( parsed.variant );
		const weaker = variant === null ? [ defaults.values ] : [ variant.values, defaults.values ];
		return new ParentContext( parent, parsed.values.chain( LookKey.BASE, ...weaker ) );
	}, [ parent, attribute, defaults, variants ] );
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
	// Written back on every edit: its actions must survive.
	const attribute = useMemo( () => BlockAttribute.fromJson( raw, definition, settings.breakpoints, settings.actions ), [ raw, definition ] );
	const { defaults, save } = useBlockDefaults( definition );
	const { variants, save: saveVariants } = useBlockVariants( definition );

	return new FbBlockState(
		attribute,
		attribute.variant === null ? null : variants.all.get( attribute.variant ),
		defaults,
		variants,
		editorInstanceClass( clientId ),
		( next ) => setAttributes( { [ ATTRIBUTE ]: next.toAttribute() } ),
		save,
		saveVariants
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
	const parent = useParentContext( clientId, definition );
	// Actions are settings: hidden while only styles are shown.
	const view = useSelect( ( select ) => select( store ).getSidebarView(), [] );
	const weaker = variant === null ? [ defaultsSource ] : [ new WeakerSource( variant.values, fromVariant( variant.name ) ), defaultsSource ];
	const makeDefault = defaults.canEdit
		? ( control: Control, look: LookKey ) =>
				state.saveDefaults( defaults.saved.withLook( look, defaults.saved.look( look ).withControlFrom( control, values.look( look ) ) ) )
		: null;

	return (
		<>
			<InspectorControls>
				<VariantPicker definition={ definition } state={ state } />
				<StatefulInspector
					definition={ definition }
					filterable
					values={ values }
					weaker={ weaker }
					listFallbackControls={ false }
					allowPlaced
					parent={ parent }
					memoryKey={ clientId }
					onChange={ ( next ) => state.setValues( next ) }
					makeDefault={ makeDefault }
				/>
				{ view !== 'styles' && (
					<ActionsPanel
						catalog={ settings.actions }
						actions={ state.attribute.actions }
						clientId={ clientId }
						onChange={ ( actions ) => state.setAttribute( state.attribute.withActions( actions ) ) }
					/>
				) }
			</InspectorControls>
			<DefaultsStyles definition={ definition } defaults={ defaults.values } root={ root } />
			{ variant !== null && <VariantStyles definition={ definition } variant={ variant } defaults={ defaults.values } root={ root } /> }
			<InstanceStyles definition={ definition } values={ values } weaker={ state.weaker() } instanceClass={ state.instanceClass } root={ root } />
			<PartOutline definition={ definition } clientId={ clientId } instanceClass={ state.instanceClass } root={ root } />
		</>
	);
}
