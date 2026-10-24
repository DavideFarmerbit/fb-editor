/**
 * Editor half of fb blocks. Everything about a block except its editor
 * markup comes from PHP: metadata via WordPress's server bootstrap (see
 * types/wordpress__blocks.d.ts), controls via the definition in settings.
 *
 * Every block's edit is wrapped: the wrapper parses values, renders the
 * generated sidebar and injects live CSS, so blocks only write markup.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { registerBlockType, type BlockEditProps } from '@wordpress/blocks';
import { createElement, Fragment, useMemo, useState } from '@wordpress/element';
import type { ReactElement, RefCallback } from 'react';

import { settings } from '../Editor/settings';
import { ATTRIBUTE } from './BlockRegistry.generated';
import { DefaultsStyles } from './Defaults/defaults-styles';
import { useBlockDefaults } from './Defaults/use-block-defaults';
import type { BlockDefinition } from './Definition/block-definition';
import { NAMESPACE } from './Definition/BlockName.generated';
import { Inspector } from './Inspector/inspector';
import { InstanceStyles } from './Rendering/instance-styles';
import { BlockValues } from './Values/block-values';

export type FbAttributes = Record< string, unknown >;

/** Props for the block root: pass to useBlockProps() as is. */
export interface RootProps {
	/** Block class + editor-only instance class (live CSS targets it). */
	readonly className: string;
	readonly ref: RefCallback< HTMLElement >;
}

export interface FbBlockEditProps extends BlockEditProps< FbAttributes > {
	readonly definition: BlockDefinition;
	/** Sanitized through the definition: never read raw attributes. */
	readonly values: BlockValues;
	/** The type's defaults: what unset values fall back to. */
	readonly defaults: BlockValues;
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
	const attribute = props.attributes[ ATTRIBUTE ];
	const values = useMemo(
		() => BlockValues.fromAttribute( attribute, definition, settings.breakpoints ),
		[ attribute, definition ]
	);
	const { defaults, save: saveDefaults } = useBlockDefaults( definition );
	// Editor-only: never saved, so it can be anything unique.
	const instanceClass = `fb-i-${ props.clientId }`;

	return createElement(
		Fragment,
		null,
		createElement(
			InspectorControls,
			null,
			createElement( Inspector, {
				definition,
				values,
				defaults,
				clientId: props.clientId,
				onChange: ( next: BlockValues ) => props.setAttributes( { [ ATTRIBUTE ]: next.toAttribute() } ),
				onChangeDefaults: saveDefaults,
			} )
		),
		createElement( DefaultsStyles, { definition, defaults: defaults.values, root } ),
		createElement( InstanceStyles, { definition, values, defaults: defaults.values, instanceClass, root } ),
		createElement( editor.edit, {
			...props,
			definition,
			values,
			defaults: defaults.values,
			rootProps: { className: `${ definition.className } ${ instanceClass }`, ref: setRoot },
		} )
	);
}

/**
 * Registers every PHP-defined block with its editor markup. A block defined
 * in PHP without editor markup (or the reverse) is a bug: reported loudly.
 */
export function registerBlocks( editors: readonly BlockEditor[] ): void {
	for ( const definition of settings.blocks ) {
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
