/**
 * Editor counterpart of InstanceStyleCollector: compiles one block's CSS
 * with the JS compiler (byte-identical to PHP, see the parity suite) and
 * portals it into the <head> of the document the block renders in, i.e.
 * the canvas iframe. Nothing is added inside the block's own markup.
 */
import { createPortal, useMemo } from '@wordpress/element';
import type { ReactElement } from 'react';

import { settings } from '../../Editor/settings';
import { CssLayer, Selector } from '../../Styles/css';
import { StyleCompiler } from '../../Styles/compiler';
import type { BlockDefinition } from '../Definition/block-definition';
import { BlockStyles } from '../Values/block-styles';
import type { StatefulValues } from '../Values/stateful-values';

const compiler = new StyleCompiler( settings.breakpoints, settings.presets );
const blockStyles = new BlockStyles( settings.breakpoints, true );

interface InstanceStylesProps {
	readonly definition: BlockDefinition;
	readonly values: StatefulValues;
	/** The type's defaults: conditions fall back to them, like in PHP. */
	readonly defaults: StatefulValues;
	/** Editor-only instance class on the block root. */
	readonly instanceClass: string;
	/** The block root, once mounted: tells us which document to write to. */
	readonly root: HTMLElement | null;
}

export function InstanceStyles( { definition, values, defaults, instanceClass, root }: InstanceStylesProps ): ReactElement | null {
	const css = useMemo(
		() => blockStyles.forSource( definition, values, Selector.forClass( instanceClass ), defaults ).css( compiler, CssLayer.Instances ),
		[ definition, values, defaults, instanceClass ]
	);

	if ( root === null || css === '' ) {
		return null;
	}

	return createPortal( <style>{ css }</style>, root.ownerDocument.head );
}
