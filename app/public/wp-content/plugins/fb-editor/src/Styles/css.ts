/**
 * Mirrors Fb\Styles\DeclarationSet and Fb\Styles\Selector, plus layer
 * wrapping. Enums and patterns come from PHP (*.generated.ts). Output format
 * must match PHP byte for byte (checked by the parity suite).
 */
import { CssLayer } from '../Css/CssLayer.generated';
import { InvalidJson } from '../Json/json';
import { CssProperty } from './CssProperty.generated';
import { REVERT } from './DeclarationSet.generated';
import { CLASS_PATTERN, PSEUDO_CLASS_PATTERN } from './Selector.generated';

export { CssLayer, CssProperty };

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
		private readonly isRoot = true
	) {}

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

	toString(): string {
		return `:where(${ this.inner })`;
	}

	private static valid( className: string ): string {
		if ( ! CLASS_PATTERN.test( className ) ) {
			throw new InvalidJson( `Invalid class name "${ className }".` );
		}
		return className;
	}
}
