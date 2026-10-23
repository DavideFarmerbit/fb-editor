/**
 * Mirrors Fb\Styles\DeclarationSet and Fb\Styles\Selector, plus layer
 * wrapping. Enums and patterns come from PHP (*.generated.ts). Output format
 * must match PHP byte for byte (checked by the parity suite).
 */
import { CssLayer } from '../Css/CssLayer.generated';
import { InvalidJson } from '../Json/json';
import type { Side } from '../Values/box';
import { CssProperty } from './CssProperty.generated';
import { BoxProperty } from './Output/BoxProperty.generated';
import { REVERT } from './DeclarationSet.generated';
import { CLASS_PATTERN } from './Selector.generated';

export { BoxProperty, CssLayer, CssProperty };

export function wrapInLayer( layer: CssLayer, css: string ): string {
	return `@layer ${ layer }{${ css }}`;
}

/**
 * `padding` + `top` → `padding-top`. Derived from the enums, so a longhand
 * missing from CssProperty fails loudly instead of being duplicated here.
 */
export function boxLonghand( property: BoxProperty, side: Side ): CssProperty {
	const name = `${ property }-${ side }`;
	const longhand = Object.values( CssProperty ).find( ( candidate ) => candidate === name );
	if ( longhand === undefined ) {
		throw new InvalidJson( `No CSS property "${ name }" in CssProperty.php.` );
	}
	return longhand;
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
	private constructor( private readonly path: readonly string[] ) {}

	static forClass( className: string ): Selector {
		return new Selector( [ Selector.valid( className ) ] );
	}

	child( className: string ): Selector {
		return new Selector( [ ...this.path, Selector.valid( className ) ] );
	}

	toString(): string {
		return `:where(.${ this.path.join( '>.' ) })`;
	}

	private static valid( className: string ): string {
		if ( ! CLASS_PATTERN.test( className ) ) {
			throw new InvalidJson( `Invalid class name "${ className }".` );
		}
		return className;
	}
}
