/**
 * Mirrors Fb\Blocks\Definition\Input\Border\BorderInput: each part of each
 * side checked on its own; invalid parts dropped, the rest kept.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { BorderBox, BorderSide } from '../../../../Values/border';
import { Side } from '../../../../Values/box';
import { colorFromJson, type Color } from '../../../../Values/color';
import { CustomLength, lengthFromJson } from '../../../../Values/length';
import { PresetReference } from '../../../../Values/preset-reference';
import { PresetKind } from '../../../../Presets/presets';
import { SpacingMode } from '../Spacing/spacing-input';

export class BorderStyleOption {
	constructor(
		public readonly value: string,
		public readonly label: string
	) {}
}

/** A part that doesn't parse is simply not there. */
function part< T >( side: JsonValue, key: string, parse: ( json: JsonValue ) => T | null ): T | null {
	if ( ! side.has( key ) ) {
		return null;
	}
	try {
		return parse( side.get( key ) );
	} catch ( error ) {
		if ( error instanceof InvalidJson ) {
			return null;
		}
		throw error;
	}
}

export class BorderInput {
	readonly kind = 'border';

	constructor(
		public readonly modes: readonly SpacingMode[],
		/** Offered styles, with labels, from PHP. */
		public readonly styles: readonly BorderStyleOption[]
	) {}

	allowsWidth( length: unknown ): length is CustomLength {
		return length instanceof CustomLength && ! length.isNegative();
	}

	/** Palette colors (presets) or custom hex, as ColorInput's default. */
	allowsColor( color: Color ): boolean {
		return ! ( color instanceof PresetReference ) || color.preset === PresetKind.Color;
	}

	parse( json: JsonValue ): BorderBox {
		if ( ! json.isObject() ) {
			throw new InvalidJson( `${ json.path }: expected an object` );
		}
		const side = ( name: Side ): BorderSide | null => {
			if ( ! json.has( name ) || ! json.get( name ).isObject() ) {
				return null;
			}
			const sideJson = json.get( name );
			const parsed = new BorderSide(
				part( sideJson, 'width', ( width ) => {
					const length = lengthFromJson( width );
					return this.allowsWidth( length ) ? length : null;
				} ),
				part( sideJson, 'style', ( style ) => {
					const value = style.string();
					return this.styles.some( ( option ) => option.value === value ) ? value : null;
				} ),
				part( sideJson, 'color', ( color ) => {
					const value = colorFromJson( color );
					return this.allowsColor( value ) ? value : null;
				} )
			);
			return parsed.isEmpty() ? null : parsed;
		};
		const box = new BorderBox( side( Side.Top ), side( Side.Right ), side( Side.Bottom ), side( Side.Left ) );
		if ( box.isEmpty() ) {
			throw new InvalidJson( `${ json.path }: no valid side` );
		}
		return box;
	}

	static fromJson( json: JsonValue ): BorderInput {
		return new BorderInput(
			json.get( 'modes' ).list().map( ( mode ) => mode.oneOf( SpacingMode ) ),
			json
				.get( 'styles' )
				.list()
				.map( ( style ) => new BorderStyleOption( style.get( 'value' ).string(), style.get( 'label' ).string() ) )
		);
	}
}
