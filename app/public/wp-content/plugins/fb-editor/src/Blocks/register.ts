/**
 * Editor half of fb blocks. Everything about a block except its editor
 * markup comes from PHP: metadata via WordPress's server bootstrap (see
 * types/wordpress__blocks.d.ts), controls via the definition in settings.
 *
 * Every block's edit is wrapped: the wrapper parses values, renders the
 * generated sidebar and injects live CSS, so blocks only write markup.
 */
import { registerBlockType, type BlockEditProps } from '@wordpress/blocks';
import { createElement, Fragment, useState } from '@wordpress/element';
import type { ReactElement, RefCallback } from 'react';

import { settings } from '../Editor/settings';
import { FbBlockChrome, useFbBlock, usePreviewClass, variantClass } from './block-chrome';
import type { BlockDefinition } from './Definition/block-definition';
import { NAMESPACE } from './Definition/BlockName.generated';
import { useHidingClasses } from './Hiding/hiding';
import type { StatefulValues } from './Values/stateful-values';

export type FbAttributes = Record< string, unknown >;

/** Props for the block root: pass to useBlockProps() as is. */
export interface RootProps {
	/** Block, variant and hide classes + editor-only instance class (live CSS targets it). */
	readonly className: string;
	readonly ref: RefCallback< HTMLElement >;
}

export interface FbBlockEditProps extends BlockEditProps< FbAttributes > {
	readonly definition: BlockDefinition;
	/** Sanitized through the definition: never read raw attributes. */
	readonly values: StatefulValues;
	/** The type's defaults: what unset values fall back to last. */
	readonly defaults: StatefulValues;
	/** The variant's values, if it uses one: what unset values fall back to first. */
	readonly variant: StatefulValues | null;
	/** Replaces the block's own values (e.g. from a placeholder's button). */
	readonly setValues: ( values: StatefulValues ) => void;
	readonly rootProps: RootProps;
}

/**
 * The editor markup of one block, keyed by its PHP `NAME` constant
 * (from the block's `<Block>.generated.ts`).
 */
export class BlockEditor {
	constructor(
		public readonly name: string,
		public readonly edit: ( props: FbBlockEditProps ) => ReactElement,
		public readonly save: () => ReactElement | null
	) {}

	fullName(): string {
		return `${ NAMESPACE }/${ this.name }`;
	}
}

interface WrapperProps extends BlockEditProps< FbAttributes > {
	readonly editor: BlockEditor;
	readonly definition: BlockDefinition;
}

function FbBlockEdit( { editor, definition, ...props }: WrapperProps ): ReactElement {
	const [ root, setRoot ] = useState< HTMLElement | null >( null );
	const state = useFbBlock( definition, props.attributes, props.setAttributes, props.clientId );
	const preview = usePreviewClass( definition, props.clientId, props.isSelected );
	const hiding = useHidingClasses( definition, [ state.values, ...state.weaker() ] );

	return createElement(
		Fragment,
		null,
		createElement( FbBlockChrome, { definition, state, clientId: props.clientId, root } ),
		createElement( editor.edit, {
			...props,
			definition,
			values: state.values,
			defaults: state.defaults.values,
			variant: state.variant?.values ?? null,
			setValues: ( values: StatefulValues ) => state.setValues( values ),
			rootProps: {
				className: [ definition.className, variantClass( definition, state.variant ), state.instanceClass, preview, ...hiding ].filter( Boolean ).join( ' ' ),
				ref: setRoot,
			},
		} )
	);
}

/**
 * Registers every PHP-defined block with its editor markup. A block defined
 * in PHP without editor markup (or the reverse) is a bug: reported loudly.
 */
export function registerBlocks( editors: readonly BlockEditor[] ): void {
	for ( const definition of settings.blocks.filter( ( candidate ) => ! candidate.isCore ) ) {
		const editor = editors.find( ( candidate ) => candidate.fullName() === definition.name );
		if ( editor === undefined ) {
			// eslint-disable-next-line no-console
			console.error( `fb: block "${ definition.name }" has no editor markup.` );
			continue;
		}

		registerBlockType< FbAttributes >( definition.name, {
			edit: ( props ) => createElement( FbBlockEdit, { ...props, editor, definition } ),
			save: editor.save,
		} );
	}

	for ( const editor of editors ) {
		if ( ! settings.blocks.some( ( definition ) => definition.name === editor.fullName() ) ) {
			// eslint-disable-next-line no-console
			console.error( `fb: editor markup for "${ editor.fullName() }" has no PHP definition.` );
		}
	}
}
