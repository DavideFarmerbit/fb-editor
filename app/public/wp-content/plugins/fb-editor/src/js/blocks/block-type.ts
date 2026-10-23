/**
 * Mirrors the identity part of Fb\Blocks\Definition\BlockDefinition (PHP).
 * Controls, layout and conditions are parsed once the controls UI exists.
 */
export class BlockType {
	constructor(
		/** e.g. "fb/section" */
		public readonly name: string,
		public readonly title: string,
		/** e.g. "fb-section" */
		public readonly className: string,
		public readonly elements: readonly string[]
	) {}

	elementClass( element: string ): string {
		return `${ this.className }__${ element }`;
	}
}
