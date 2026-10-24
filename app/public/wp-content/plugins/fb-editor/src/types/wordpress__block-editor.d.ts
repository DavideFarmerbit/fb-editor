/**
 * @wordpress/block-editor ships no type declarations. These cover exactly
 * the APIs fb uses; extend as needed. Verified against WordPress 7.1.3.
 */
declare module '@wordpress/block-editor' {
	import type { ComponentType, HTMLAttributes, ReactNode, RefCallback } from 'react';

	export interface BlockProps extends HTMLAttributes< HTMLElement > {
		/** A merged callback ref; fits any element tag. */
		ref: RefCallback< HTMLElement >;
		className: string;
	}

	export interface BlockPropsInput extends Partial< HTMLAttributes< HTMLElement > > {
		/** Merged with the editor's own ref. */
		ref?: RefCallback< HTMLElement >;
	}

	export function useBlockProps( props?: BlockPropsInput ): BlockProps;

	export function useInnerBlocksProps(
		props?: Partial< HTMLAttributes< HTMLElement > >,
		options?: Readonly< Record< string, unknown > >
	): BlockProps & { children: ReactNode };

	export const InnerBlocks: ComponentType< Readonly< Record< string, unknown > > > & {
		Content: ComponentType;
	};

	/** Fills the block sidebar ("Settings" tab by default). */
	export const InspectorControls: ComponentType< { children?: ReactNode; group?: string } >;
}
