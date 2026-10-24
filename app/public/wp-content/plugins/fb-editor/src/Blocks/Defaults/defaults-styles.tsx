/**
 * Editor counterpart of DefaultsStylesheet: the block type's `fb.defaults`
 * layer in the canvas, shared by every block of that type, updated live as
 * defaults are edited.
 */
import { useMemo } from '@wordpress/element';

import { settings } from '../../Editor/settings';
import { CssLayer, Selector } from '../../Styles/css';
import { StyleCompiler } from '../../Styles/compiler';
import type { BlockDefinition } from '../Definition/block-definition';
import { useDocumentStyle } from '../Rendering/document-style';
import { BlockStyles } from '../Values/block-styles';
import type { BlockValues } from '../Values/block-values';

const compiler = new StyleCompiler( settings.breakpoints, settings.presets );
const blockStyles = new BlockStyles( settings.breakpoints );

interface DefaultsStylesProps {
	readonly definition: BlockDefinition;
	readonly defaults: BlockValues;
	/** Any block root in the canvas: tells us which document to write to. */
	readonly root: HTMLElement | null;
}

export function DefaultsStyles( { definition, defaults, root }: DefaultsStylesProps ): null {
	const css = useMemo(
		() => compiler.compile( CssLayer.Defaults, blockStyles.bindings( definition, defaults, Selector.forClass( definition.className ) ) ),
		[ definition, defaults ]
	);

	useDocumentStyle( root?.ownerDocument ?? null, `defaults/${ definition.name }`, css );

	return null;
}
