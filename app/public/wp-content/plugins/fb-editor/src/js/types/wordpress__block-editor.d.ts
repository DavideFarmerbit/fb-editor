/**
 * @wordpress/block-editor ships no type declarations. These cover exactly
 * the APIs fb uses; extend as needed. Verified against WordPress 7.1.3.
 */
declare module '@wordpress/block-editor' {
	import type { ComponentType, HTMLAttributes, RefCallback } from 'react';

	export interface BlockProps extends HTMLAttributes< HTMLElement > {
		/** A merged callback ref; fits any element tag. */
		ref: RefCallback< HTMLElement >;
		className: string;
	}

	export function useBlockProps( props?: Partial< HTMLAttributes< HTMLElement > > ): BlockProps;

	export function useInnerBlocksProps(
		props?: Partial< HTMLAttributes< HTMLElement > >,
		options?: Readonly< Record< string, unknown > >
	): BlockProps & { children: React.ReactNode };

	export const InnerBlocks: ComponentType< Readonly< Record< string, unknown > > > & {
		Content: ComponentType;
	};
}
