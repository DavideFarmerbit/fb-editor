/**
 * Mirrors Fb\Blocks\Parts\Part: a part of a block styled from its sidebar
 * (::before, an image, list markers), reached from the block root the same
 * way as in PHP. Its own definition (settings, panels, the block's states)
 * comes from PHP.
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import { PseudoElement, type Selector } from '../../Styles/css';
import type { BlockDefinition } from '../Definition/block-definition';
import { parseElementId, type ElementId } from '../Definition/element-id';
import { PATTERN } from './PartId.generated';
import { PartKind } from './PartKind.generated';

export { PartKind };

declare const partIdBrand: unique symbol;

export type PartId = string & { readonly [ partIdBrand ]: true };

export function parsePartId( json: JsonValue ): PartId {
	const value = json.string();
	if ( ! PATTERN.test( value ) ) {
		throw new InvalidJson( `${ json.path }: invalid part id` );
	}
	return value as PartId;
}

export class Part {
	constructor(
		public readonly id: PartId,
		public readonly label: string,
		public readonly kind: PartKind,
		public readonly element: ElementId | null,
		public readonly tag: string | null,
		/** The tag is a direct child (not any descendant). */
		public readonly childTag: boolean,
		public readonly pseudoElement: PseudoElement | null,
		/** What it's styled with. */
		public readonly definition: BlockDefinition
	) {}

	/**
	 * Mirrors Part::selector(), from the block root's selector (states
	 * applied). `editor`: core blocks' elements keep core's classes there.
	 */
	selector( root: Selector, block: BlockDefinition, editor: boolean ): Selector {
		let selector = root;
		if ( this.element !== null ) {
			selector = selector.child( editor ? block.editorElementClass( this.element ) : block.elementClass( this.element ) );
		}
		if ( this.tag !== null ) {
			selector = this.childTag ? selector.childTag( this.tag ) : selector.descendantTag( this.tag );
		}
		return this.pseudoElement === null ? selector : selector.withPseudoElement( this.pseudoElement );
	}

	/** `definitionFromJson`: passed in to avoid a module cycle with block-definition.ts. */
	static fromJson( json: JsonValue, definitionFromJson: ( json: JsonValue ) => BlockDefinition ): Part {
		return new Part(
			parsePartId( json.get( 'id' ) ),
			json.get( 'label' ).string(),
			json.get( 'kind' ).oneOf( PartKind ),
			json.get( 'element' ).nullable( parseElementId ),
			json.get( 'tag' ).nullable( ( tag ) => tag.string() ),
			json.get( 'childTag' ).boolean(),
			json.get( 'pseudoElement' ).nullable( ( pseudo ) => pseudo.oneOf( PseudoElement ) ),
			definitionFromJson( json.get( 'definition' ) )
		);
	}
}
