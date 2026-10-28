/**
 * Mirrors Fb\Styles\DeclarationSet and Fb\Styles\Selector, plus layer
 * wrapping. Enums and patterns come from PHP (*.generated.ts). Output format
 * must match PHP byte for byte (checked by the parity suite).
 */
import { CssLayer } from '../Css/CssLayer.generated';
import { InvalidJson } from '../Json/json';
import { CssProperty } from './CssProperty.generated';
import { REVERT } from './DeclarationSet.generated';
import { PseudoElement } from './PseudoElement.generated';
import { CLASS_PATTERN, PSEUDO_CLASS_PATTERN, TAG_PATTERN } from './Selector.generated';

export { CssLayer, CssProperty, PseudoElement };

export function wrapInLayer( layer: CssLayer, css: string ): string {
	return `@layer ${ layer }{${ css }}`;
}

/** Mirrors CssLayer::states(): where a base layer's state styles go. */
export function statesLayer( layer: CssLayer ): CssLayer {
	switch ( layer ) {
		case CssLayer.Defaults:
			return CssLayer.StateDefaults;
		case CssLayer.Variants:
			return CssLayer.StateVariants;
		case CssLayer.Instances:
			return CssLayer.StateInstances;
		default:
			throw new Error( `No state layer for ${ layer }.` );
	}
}

export { REVERT };

/** Immutable, ordered `property: value` set; later writes win in place. */
export class DeclarationSet {
	private constructor( private readonly values: ReadonlyMap< CssProperty, string > ) {}

	static empty(): DeclarationSet {
		return new DeclarationSet( new Map() );
	}

	with( property: CssProperty, value: string ): DeclarationSet {
		return new DeclarationSet( new Map( this.values ).set( property, value ) );
	}

	merge( other: DeclarationSet ): DeclarationSet {
		const values = new Map( this.values );
		other.values.forEach( ( value, property ) => values.set( property, value ) );
		return new DeclarationSet( values );
	}

	get( property: CssProperty ): string | null {
		return this.values.get( property ) ?? null;
	}

	get size(): number {
		return this.values.size;
	}

	toCss(): string {
		let css = '';
		this.values.forEach( ( value, property ) => {
			css += `${ property }:${ value };`;
		} );
		return css;
	}
}

/** Zero-specificity selector; elements are direct children of the root. */
export class Selector {
	private constructor(
		/** Built only from validated parts. */
		private readonly inner: string,
		/** Still just the root class (+ pseudo-classes): no child yet. */
		private readonly isRoot = true,
		/** Generated box drawn on the selected element. */
		private readonly pseudoElement: PseudoElement | null = null
	) {}

	/** A generated box of the selected element: goes after :where(), specificity still zero. */
	withPseudoElement( pseudoElement: PseudoElement ): Selector {
		return new Selector( this.inner, this.isRoot, pseudoElement );
	}

	static forClass( className: string ): Selector {
		return new Selector( `.${ Selector.valid( className ) }` );
	}

	/** The root, only while it matches this pseudo-class (a state). */
	withPseudoClass( pseudoClass: string ): Selector {
		if ( ! this.isRoot ) {
			throw new Error( 'Pseudo-classes go on the root, before any child.' );
		}
		if ( ! PSEUDO_CLASS_PATTERN.test( pseudoClass ) ) {
			throw new InvalidJson( `Invalid pseudo-class "${ pseudoClass }".` );
		}
		return new Selector( this.inner + pseudoClass );
	}

	child( className: string ): Selector {
		return new Selector( `${ this.inner }>.${ Selector.valid( className ) }`, false );
	}

	/**
	 * Editor only: matches only inside an element carrying all these classes
	 * (the canvas page previewed with some media conditions). Still zero
	 * specificity, inside :where().
	 */
	inside( classNames: readonly string[] ): Selector {
		if ( classNames.length === 0 ) {
			return this;
		}
		return new Selector( `${ classNames.map( ( name ) => `.${ Selector.valid( name ) }` ).join( '' ) } ${ this.inner }`, false, this.pseudoElement );
	}

	/** A direct child of this HTML tag. */
	childTag( tag: string ): Selector {
		return new Selector( `${ this.inner }>${ Selector.validTag( tag ) }`, false );
	}

	/** Any descendant of this HTML tag. */
	descendantTag( tag: string ): Selector {
		return new Selector( `${ this.inner } ${ Selector.validTag( tag ) }`, false );
	}

	toString(): string {
		return `:where(${ this.inner })` + ( this.pseudoElement === null ? '' : `::${ this.pseudoElement }` );
	}

	private static validTag( tag: string ): string {
		if ( ! TAG_PATTERN.test( tag ) ) {
			throw new InvalidJson( `Invalid tag name "${ tag }".` );
		}
		return tag;
	}

	private static valid( className: string ): string {
		if ( ! CLASS_PATTERN.test( className ) ) {
			throw new InvalidJson( `Invalid class name "${ className }".` );
		}
		return className;
	}
}
