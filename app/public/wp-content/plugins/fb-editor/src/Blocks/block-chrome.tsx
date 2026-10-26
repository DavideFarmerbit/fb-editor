/**
 * What every block with fb controls gets in the editor, whether an fb block
 * or a managed core block: parsed values, type defaults, the generated
 * sidebar, and live CSS (defaults + instance) in the canvas.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import type { ReactElement } from 'react';

import { settings } from '../Editor/settings';
import { ATTRIBUTE } from './BlockRegistry.generated';
import { DefaultsStyles } from './Defaults/defaults-styles';
import { useBlockDefaults, type BlockDefaults } from './Defaults/use-block-defaults';
import type { BlockDefinition, Control } from './Definition/block-definition';
import { fromDefault } from './Inspector/control-reading';
import { Inspector } from './Inspector/inspector';
import { InstanceStyles } from './Rendering/instance-styles';
import { BlockValues } from './Values/block-values';

export class FbBlockState {
	constructor(
		public readonly values: BlockValues,
		public readonly defaults: BlockDefaults,
		/** Editor-only class on the block root; never saved. */
		public readonly instanceClass: string,
		public readonly setValues: ( values: BlockValues ) => void,
		public readonly saveDefaults: ( saved: BlockValues ) => void
	) {}
}

/**
 * Editor-only instance class: stable per block while editing (the frontend
 * hash depends on values, so it would change on every edit).
 */
export function editorInstanceClass( clientId: string ): string {
	return `fb-i-${ clientId }`;
}

export function useFbBlock(
	definition: BlockDefinition,
	attributes: Readonly< Record< string, unknown > >,
	setAttributes: ( attributes: Record< string, unknown > ) => void,
	clientId: string
): FbBlockState {
	const attribute = attributes[ ATTRIBUTE ];
	const values = useMemo( () => BlockValues.fromAttribute( attribute, definition, settings.breakpoints ), [ attribute, definition ] );
	const { defaults, save } = useBlockDefaults( definition );

	return new FbBlockState(
		values,
		defaults,
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
	const { values, defaults } = state;

	return (
		<>
			<InspectorControls>
				<Inspector
					definition={ definition }
					values={ values }
					fallback={ defaults.values }
					fallbackStatus={ fromDefault( definition.title ) }
					memoryKey={ clientId }
					listFallbackControls={ false }
					onChange={ state.setValues }
					makeDefault={
						defaults.canEdit ? ( control: Control ) => state.saveDefaults( defaults.saved.withControlFrom( control, values ) ) : null
					}
				/>
			</InspectorControls>
			<DefaultsStyles definition={ definition } defaults={ defaults.values } root={ root } />
			<InstanceStyles definition={ definition } values={ values } defaults={ defaults.values } instanceClass={ state.instanceClass } root={ root } />
		</>
	);
}
