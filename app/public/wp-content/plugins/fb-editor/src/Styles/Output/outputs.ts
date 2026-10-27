/**
 * Mirrors Fb\Styles\Output\*: declarative value → declarations mappings.
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import { BooleanValue } from '../../Values/boolean';
import { BoxValue, SIDES } from '../../Values/box';
import { KeywordValue } from '../../Values/keyword';
import { NumberValue } from '../../Values/number';
import type { Presets } from '../../Presets/presets';
import { isCssValue, type ResponsiveValue } from '../../Values/value';
import { BoxProperty, boxLonghand, CssProperty, DeclarationSet } from '../css';

export interface StyleOutput {
	/** Every property this output can write (used to revert it when hidden). */
	properties(): readonly CssProperty[];
	/** Declarations for a resolved value; wrong types and unset parts produce none. */
	declarations( value: ResponsiveValue | null, presets: Presets ): DeclarationSet;
}

export class BoxOutput implements StyleOutput {
	constructor( public readonly property: BoxProperty ) {}

	properties(): CssProperty[] {
		return SIDES.map( ( side ) => boxLonghand( this.property, side ) );
	}

	declarations( value: ResponsiveValue | null, presets: Presets ): DeclarationSet {
		let declarations = DeclarationSet.empty();
		if ( ! ( value instanceof BoxValue ) ) {
			return declarations;
		}
		for ( const side of SIDES ) {
			const length = value.get( side );
			if ( length !== null ) {
				declarations = declarations.with( boxLonghand( this.property, side ), length.toCss( presets ) );
			}
		}
		return declarations;
	}
}

export class AxisOutput implements StyleOutput {
	constructor(
		public readonly block: CssProperty,
		public readonly inline: CssProperty
	) {}

	properties(): CssProperty[] {
		return [ this.block, this.inline ];
	}

	declarations( value: ResponsiveValue | null, presets: Presets ): DeclarationSet {
		let declarations = DeclarationSet.empty();
		if ( ! ( value instanceof BoxValue ) ) {
			return declarations;
		}
		if ( value.top !== null ) {
			declarations = declarations.with( this.block, value.top.toCss( presets ) );
		}
		if ( value.left !== null ) {
			declarations = declarations.with( this.inline, value.left.toCss( presets ) );
		}
		return declarations;
	}
}

export class PropertyOutput implements StyleOutput {
	constructor( private readonly targets: readonly CssProperty[] ) {}

	properties(): readonly CssProperty[] {
		return this.targets;
	}

	declarations( value: ResponsiveValue | null, presets: Presets ): DeclarationSet {
		let declarations = DeclarationSet.empty();
		if ( ! isCssValue( value ) ) {
			return declarations;
		}
		for ( const property of this.targets ) {
			declarations = declarations.with( property, value.toCss( presets ) );
		}
		return declarations;
	}
}

/** BooleanValue → one keyword per state (e.g. flex-wrap: wrap / nowrap). */
export class ToggleOutput implements StyleOutput {
	constructor(
		public readonly property: CssProperty,
		public readonly on: KeywordValue,
		public readonly off: KeywordValue
	) {}

	properties(): CssProperty[] {
		return [ this.property ];
	}

	declarations( value: ResponsiveValue | null ): DeclarationSet {
		if ( ! ( value instanceof BooleanValue ) ) {
			return DeclarationSet.empty();
		}
		return DeclarationSet.empty().with( this.property, ( value.on ? this.on : this.off ).toCss() );
	}
}

/** Mirrors SpanOutput: a whole number of grid tracks (grid-column: span 2). */
export class SpanOutput implements StyleOutput {
	constructor( public readonly property: CssProperty ) {}

	properties(): CssProperty[] {
		return [ this.property ];
	}

	declarations( value: ResponsiveValue | null ): DeclarationSet {
		if ( ! ( value instanceof NumberValue ) || ! Number.isInteger( value.value ) || value.value < 1 ) {
			return DeclarationSet.empty();
		}
		return DeclarationSet.empty().with( this.property, `span ${ value.value }` );
	}
}

export function outputFromJson( json: JsonValue ): StyleOutput {
	const kind = json.get( 'kind' ).string();
	switch ( kind ) {
		case 'box':
			return new BoxOutput( json.get( 'property' ).oneOf( BoxProperty ) );
		case 'axis':
			return new AxisOutput( json.get( 'block' ).oneOf( CssProperty ), json.get( 'inline' ).oneOf( CssProperty ) );
		case 'property': {
			const properties = json.get( 'properties' ).list().map( ( property ) => property.oneOf( CssProperty ) );
			if ( properties.length === 0 ) {
				throw new InvalidJson( `${ json.path }.properties: expected at least one property` );
			}
			return new PropertyOutput( properties );
		}
		case 'toggle':
			return new ToggleOutput(
				json.get( 'property' ).oneOf( CssProperty ),
				new KeywordValue( json.get( 'on' ).string() ),
				new KeywordValue( json.get( 'off' ).string() )
			);
		case 'span':
			return new SpanOutput( json.get( 'property' ).oneOf( CssProperty ) );
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown output kind "${ kind }"` );
	}
}
