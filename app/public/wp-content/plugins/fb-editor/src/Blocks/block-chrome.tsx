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
import { StatefulValues } from './Values/stateful-values';

export class FbBlockState {
	constructor(
		public readonly values: StatefulValues,
		public readonly defaults: BlockDefaults,
		/** Editor-only class on the block root; never saved. */
		public readonly instanceClass: string,
		public readonly setValues: ( values: StatefulValues ) => void,
		public readonly saveDefaults: ( saved: StatefulValues ) => void
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
	const values = useMemo( () => StatefulValues.fromJson( attribute, definition, settings.breakpoints ), [ attribute, definition ] );
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
					values={ values.base }
					fallback={ defaults.values.base }
					fallbackStatus={ fromDefault( definition.title ) }
					memoryKey={ clientId }
					listFallbackControls={ false }
					onChange={ ( base ) => state.setValues( values.withBase( base ) ) }
					makeDefault={
						defaults.canEdit
							? ( control: Control ) =>
									state.saveDefaults( defaults.saved.withBase( defaults.saved.base.withControlFrom( control, values.base ) ) )
							: null
					}
				/>
			</InspectorControls>
			<DefaultsStyles definition={ definition } defaults={ defaults.values } root={ root } />
			<InstanceStyles definition={ definition } values={ values } defaults={ defaults.values } instanceClass={ state.instanceClass } root={ root } />
		</>
	);
}
