/**
 * Mirrors Fb\Styles\Output\*: declarative value → declarations mappings.
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import { BoxValue, SIDES } from '../../Values/box';
import type { PresetVariables } from '../../Presets/presets';
import { isCssValue, type ResponsiveValue } from '../../Values/value';
import { BoxProperty, boxLonghand, CssProperty, DeclarationSet } from '../css';

export interface StyleOutput {
	/** Every property this output can write (used to revert it when hidden). */
	properties(): readonly CssProperty[];
	/** Declarations for a resolved value; wrong types and unset parts produce none. */
	declarations( value: ResponsiveValue | null, presets: PresetVariables ): DeclarationSet;
}

export class BoxOutput implements StyleOutput {
	constructor( public readonly property: BoxProperty ) {}

	properties(): CssProperty[] {
		return SIDES.map( ( side ) => boxLonghand( this.property, side ) );
	}

	declarations( value: ResponsiveValue | null, presets: PresetVariables ): DeclarationSet {
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

	declarations( value: ResponsiveValue | null, presets: PresetVariables ): DeclarationSet {
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

	declarations( value: ResponsiveValue | null, presets: PresetVariables ): DeclarationSet {
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
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown output kind "${ kind }"` );
	}
}
