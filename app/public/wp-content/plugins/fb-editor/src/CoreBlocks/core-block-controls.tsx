/**
 * fb controls on the core blocks fb manages (paragraph, heading, image…).
 * Core renders them; through public filters fb adds:
 * - its `fb` attribute (the editor registers core blocks from its own
 *   metadata, so the server's attribute alone isn't enough);
 * - the sidebar and live CSS, next to core's edit (editor.BlockEdit),
 *   whose own styling UI is turned off where props allow (image sizing);
 * - the block class + instance class on the wrapper (editor.BlockListBlock).
 *
 * Must load before core blocks register (they do on domReady).
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import type { ComponentType, ReactElement } from 'react';

import { canvasBlockElement, NO_IMAGE_RESIZE_CONTEXT } from '../Adapter/adapter';
import { editorInstanceClass, FbBlockChrome, useFbBlock, usePreviewClass, useVariantOf, variantClass } from '../Blocks/block-chrome';
import { ATTRIBUTE, ATTRIBUTE_TYPE } from '../Blocks/BlockRegistry.generated';
import { useBlockDefaults } from '../Blocks/Defaults/use-block-defaults';
import type { BlockDefinition } from '../Blocks/Definition/block-definition';
import { useHidingClasses } from '../Blocks/Hiding/hiding';
import { BlockAttribute } from '../Blocks/Values/block-attribute';
import { ActionCatalog } from '../Actions/action-catalog';
import { settings } from '../Editor/settings';
import './core-block-controls.scss';

function coreDefinition( name: string ): BlockDefinition | undefined {
	return settings.blocks.find( ( definition ) => definition.isCore && definition.name === name );
}

/** Block settings as the filter receives them (core's metadata, boundary). */
type BlockSettings = Record< string, unknown >;

addFilter( 'blocks.registerBlockType', 'fb/core-block-attribute', ( blockSettings: BlockSettings, name: string ): BlockSettings => {
	if ( coreDefinition( name ) === undefined ) {
		return blockSettings;
	}
	const attributes = typeof blockSettings.attributes === 'object' && blockSettings.attributes !== null ? blockSettings.attributes : {};
	return { ...blockSettings, attributes: { ...attributes, [ ATTRIBUTE ]: { type: ATTRIBUTE_TYPE, default: {} } } };
} );

/** The props editor.BlockEdit / editor.BlockListBlock components receive. */
interface CoreBlockProps {
	readonly name: string;
	readonly clientId: string;
	readonly attributes: Readonly< Record< string, unknown > >;
	readonly setAttributes: ( attributes: Record< string, unknown > ) => void;
	readonly className?: string;
	readonly isSelected?: boolean;
	/** Block context, as core computed it. */
	readonly context?: Readonly< Record< string, unknown > >;
}

/** Context core's edit of a block gets instead of its own, by block name. */
const CONTEXT_OVERRIDES: Readonly< Record< string, Readonly< Record< string, unknown > > > > = {
	'core/image': NO_IMAGE_RESIZE_CONTEXT,
};

/** The block's wrapper in the canvas, found once it's mounted. */
function useCanvasBlockElement( clientId: string ): HTMLElement | null {
	const [ element, setElement ] = useState< HTMLElement | null >( null );
	useEffect( () => {
		setElement( canvasBlockElement( clientId ) );
	} );
	return element;
}

function CoreBlockChrome( { definition, ...props }: CoreBlockProps & { readonly definition: BlockDefinition } ): ReactElement {
	const state = useFbBlock( definition, props.attributes, props.setAttributes, props.clientId );
	const root = useCanvasBlockElement( props.clientId );
	return <FbBlockChrome definition={ definition } state={ state } clientId={ props.clientId } root={ root } />;
}

function withContextOverrides( props: CoreBlockProps ): Pick< CoreBlockProps, 'context' > {
	const overrides = CONTEXT_OVERRIDES[ props.name ];
	return overrides === undefined ? {} : { context: { ...props.context, ...overrides } };
}

addFilter(
	'editor.BlockEdit',
	'fb/core-block-controls',
	createHigherOrderComponent(
		( BlockEdit: ComponentType< CoreBlockProps > ) =>
			function WithFbControls( props: CoreBlockProps ): ReactElement {
				const definition = coreDefinition( props.name );
				return (
					<>
						<BlockEdit { ...props } { ...withContextOverrides( props ) } />
						{ definition !== undefined && <CoreBlockChrome { ...props } definition={ definition } /> }
					</>
				);
			},
		'withFbControls'
	)
);

/**
 * Same classes as the frontend root (block, variant, hide), plus the
 * editor-only instance class and, while a state is inspected, its preview
 * class.
 */
function CoreBlockListBlock( {
	definition,
	BlockListBlock,
	...props
}: CoreBlockProps & { readonly definition: BlockDefinition; readonly BlockListBlock: ComponentType< CoreBlockProps > } ): ReactElement {
	const preview = usePreviewClass( definition, props.clientId, props.isSelected === true );
	const raw = props.attributes[ ATTRIBUTE ];
	const variant = useVariantOf( definition, raw );
	const { defaults } = useBlockDefaults( definition );
	// Read only (values, for the hide classes): actions aren't needed.
	const own = useMemo( () => BlockAttribute.fromJson( raw, definition, settings.breakpoints, ActionCatalog.EMPTY ).values, [ raw, definition ] );
	const hiding = useHidingClasses( definition, variant === null ? [ own, defaults.values ] : [ own, variant.values, defaults.values ] );
	const className = [ props.className, definition.className, variantClass( definition, variant ), editorInstanceClass( props.clientId ), preview, ...hiding ]
		.filter( Boolean )
		.join( ' ' );
	return <BlockListBlock { ...props } className={ className } />;
}

addFilter(
	'editor.BlockListBlock',
	'fb/core-block-classes',
	createHigherOrderComponent(
		( BlockListBlock: ComponentType< CoreBlockProps > ) =>
			function WithFbClasses( props: CoreBlockProps ): ReactElement {
				const definition = coreDefinition( props.name );
				if ( definition === undefined ) {
					return <BlockListBlock { ...props } />;
				}
				return <CoreBlockListBlock { ...props } definition={ definition } BlockListBlock={ BlockListBlock } />;
			},
		'withFbClasses'
	)
);
