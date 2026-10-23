/**
 * Editor half of fb blocks. Everything about a block except its editor
 * markup comes from PHP: metadata via WordPress's server bootstrap (see
 * types/wordpress__blocks.d.ts), controls via the definition in settings.
 */
import { registerBlockType, type BlockEditProps } from '@wordpress/blocks';
import type { ReactElement } from 'react';

import type { BlockDefinition } from './Definition/block-definition';
import { NAMESPACE } from './Definition/BlockName.generated';
import { BlockValues } from './Values/block-values';
import { ATTRIBUTE } from './BlockRegistry.generated';
import { settings } from '../Editor/settings';

export type FbAttributes = Record< string, unknown >;

export interface FbBlockEditProps extends BlockEditProps< FbAttributes > {
	readonly definition: BlockDefinition;
	/** Sanitized through the definition: never read raw attributes. */
	readonly values: BlockValues;
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
			edit: ( props ) =>
				editor.edit( {
					...props,
					definition,
					values: BlockValues.fromAttribute( props.attributes[ ATTRIBUTE ], definition, settings.breakpoints ),
				} ),
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
