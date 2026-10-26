/**
 * Editor counterpart of VariantsStylesheet: one variant's `fb.variants`
 * layers in the canvas, shared by every block using it, updated live as the
 * variant is edited.
 */
import { useMemo } from '@wordpress/element';

import { settings } from '../../Editor/settings';
import { CssLayer, Selector } from '../../Styles/css';
import { StyleCompiler } from '../../Styles/compiler';
import type { BlockDefinition } from '../Definition/block-definition';
import { useDocumentStyle } from '../Rendering/document-style';
import { BlockStyles } from '../Values/block-styles';
import type { StatefulValues } from '../Values/stateful-values';
import type { Variant } from './variant-set';

const compiler = new StyleCompiler( settings.breakpoints, settings.presets );
const blockStyles = new BlockStyles( settings.breakpoints, true );

interface VariantStylesProps {
	readonly definition: BlockDefinition;
	readonly variant: Variant;
	/** The type's defaults: the variant's conditions fall back to them. */
	readonly defaults: StatefulValues;
	/** Any block root in the canvas: tells us which document to write to. */
	readonly root: HTMLElement | null;
}

export function VariantStyles( { definition, variant, defaults, root }: VariantStylesProps ): null {
	const className = definition.variantClass( variant.slug );
	const css = useMemo(
		() => blockStyles.forSource( definition, variant.values, Selector.forClass( className ), defaults ).css( compiler, CssLayer.Variants ),
		[ definition, variant, defaults, className ]
	);

	useDocumentStyle( root?.ownerDocument ?? null, `variants/${ className }`, css );

	return null;
}
