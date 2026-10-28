/**
 * Mirrors Fb\Styles\Output\*: declarative value → declarations mappings.
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import { BooleanValue } from '../../Values/boolean';
import { BoxValue, SIDES } from '../../Values/box';
import { KeywordValue } from '../../Values/keyword';
import { NumberValue } from '../../Values/number';
import { BorderBox } from '../../Values/border';
import { SvgValue, TextValue } from '../../Values/content';
import { sanitizeSvg } from '../../Blocks/Definition/Input/Svg/svg';
import type { Presets } from '../../Presets/presets';
import { isCssValue, type ResponsiveValue } from '../../Values/value';
import { CssProperty, DeclarationSet } from '../css';
import { Side } from '../../Values/box';

export interface StyleOutput {
	/** Every property this output can write (used to revert it when hidden). */
	properties(): readonly CssProperty[];
	/** Declarations for a resolved value; wrong types and unset parts produce none. */
	declarations( value: ResponsiveValue | null, presets: Presets ): DeclarationSet;
}

/** Mirrors BoxOutput: one longhand per side, as computed by PHP. */
export class BoxOutput implements StyleOutput {
	constructor( private readonly longhands: Readonly< Record< Side, CssProperty > > ) {}

	properties(): CssProperty[] {
		return SIDES.map( ( side ) => this.longhands[ side ] );
	}

	declarations( value: ResponsiveValue | null, presets: Presets ): DeclarationSet {
		let declarations = DeclarationSet.empty();
		if ( ! ( value instanceof BoxValue ) ) {
			return declarations;
		}
		for ( const side of SIDES ) {
			const length = value.get( side );
			if ( length !== null ) {
				declarations = declarations.with( this.longhands[ side ], length.toCss( presets ) );
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

/** Mirrors ContentOutput: text as a CSS string (`content: "→"`), \ " and < escaped. */
export class ContentOutput implements StyleOutput {
	properties(): CssProperty[] {
		return [ CssProperty.Content ];
	}

	declarations( value: ResponsiveValue | null ): DeclarationSet {
		if ( ! ( value instanceof TextValue ) ) {
			return DeclarationSet.empty();
		}
		const escaped = value.text.replace( /[\\"<]/g, ( char ) => ( char === '<' ? '\\3C ' : `\\${ char }` ) );
		return DeclarationSet.empty().with( CssProperty.Content, `"${ escaped }"` );
	}
}

/** Mirrors PHP's rawurlencode(): encodeURIComponent also encodes !'()*. */
function rawUrlEncode( text: string ): string {
	return encodeURIComponent( text ).replace( /[!'()*]/g, ( char ) => `%${ char.charCodeAt( 0 ).toString( 16 ).toUpperCase() }` );
}

/**
 * Mirrors MaskIconOutput. The editor sanitizes with the browser's parser,
 * PHP with its own: same allowlists, possibly different serialization, so
 * this output is not part of the byte-for-byte parity suite.
 */
export class MaskIconOutput implements StyleOutput {
	properties(): CssProperty[] {
		return [ CssProperty.IconVar, CssProperty.IconContentVar ];
	}

	declarations( value: ResponsiveValue | null ): DeclarationSet {
		const markup = value instanceof SvgValue ? sanitizeSvg( value.markup ) : null;
		if ( markup === null ) {
			return DeclarationSet.empty();
		}
		return DeclarationSet.empty()
			.with( CssProperty.IconVar, `url("data:image/svg+xml,${ rawUrlEncode( markup ) }")` )
			.with( CssProperty.IconContentVar, '""' );
	}
}

/** Mirrors BorderOutput: per side, only the parts set. */
export class BorderOutput implements StyleOutput {
	properties(): CssProperty[] {
		return SIDES.flatMap( ( side ) => [ ...BorderOutput.longhands( side ) ] );
	}

	declarations( value: ResponsiveValue | null, presets: Presets ): DeclarationSet {
		let declarations = DeclarationSet.empty();
		if ( ! ( value instanceof BorderBox ) ) {
			return declarations;
		}
		for ( const side of SIDES ) {
			const border = value.get( side );
			if ( border === null ) {
				continue;
			}
			const [ width, style, color ] = BorderOutput.longhands( side );
			if ( border.width !== null ) {
				declarations = declarations.with( width, border.width.toCss() );
			}
			if ( border.style !== null ) {
				declarations = declarations.with( style, border.style );
			}
			if ( border.color !== null ) {
				declarations = declarations.with( color, border.color.toCss( presets ) );
			}
		}
		return declarations;
	}

	private static longhands( side: Side ): readonly [ CssProperty, CssProperty, CssProperty ] {
		switch ( side ) {
			case Side.Top:
				return [ CssProperty.BorderTopWidth, CssProperty.BorderTopStyle, CssProperty.BorderTopColor ];
			case Side.Right:
				return [ CssProperty.BorderRightWidth, CssProperty.BorderRightStyle, CssProperty.BorderRightColor ];
			case Side.Bottom:
				return [ CssProperty.BorderBottomWidth, CssProperty.BorderBottomStyle, CssProperty.BorderBottomColor ];
			case Side.Left:
				return [ CssProperty.BorderLeftWidth, CssProperty.BorderLeftStyle, CssProperty.BorderLeftColor ];
		}
	}
}

export function outputFromJson( json: JsonValue ): StyleOutput {
	const kind = json.get( 'kind' ).string();
	switch ( kind ) {
		case 'box': {
			const longhands = json.get( 'properties' );
			return new BoxOutput( {
				top: longhands.get( 'top' ).oneOf( CssProperty ),
				right: longhands.get( 'right' ).oneOf( CssProperty ),
				bottom: longhands.get( 'bottom' ).oneOf( CssProperty ),
				left: longhands.get( 'left' ).oneOf( CssProperty ),
			} );
		}
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
		case 'content':
			return new ContentOutput();
		case 'span':
			return new SpanOutput( json.get( 'property' ).oneOf( CssProperty ) );
		case 'mask-icon':
			return new MaskIconOutput();
		case 'border':
			return new BorderOutput();
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown output kind "${ kind }"` );
	}
}
