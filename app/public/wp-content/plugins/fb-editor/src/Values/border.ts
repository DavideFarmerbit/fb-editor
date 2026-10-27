/**
 * Mirrors Fb\Values\{BorderSide, BorderBox}: a border per side, each side's
 * width, style and color optional.
 */
import { Side, SIDES } from './box';
import type { Color } from './color';
import type { CustomLength } from './length';
import type { ResponsiveValue } from './value';

export class BorderSide {
	constructor(
		public readonly width: CustomLength | null,
		/** A style keyword from BorderStyle.php (checked by BorderInput). */
		public readonly style: string | null,
		public readonly color: Color | null
	) {}

	isEmpty(): boolean {
		return this.width === null && this.style === null && this.color === null;
	}

	/** Mirrors BorderSide::inheritFrom(). */
	inheritFrom( larger: BorderSide | null ): BorderSide {
		return larger === null ? this : new BorderSide( this.width ?? larger.width, this.style ?? larger.style, this.color ?? larger.color );
	}

	toJSON(): Record< string, unknown > {
		const json: Record< string, unknown > = {};
		if ( this.width !== null ) {
			json.width = this.width;
		}
		if ( this.style !== null ) {
			json.style = this.style;
		}
		if ( this.color !== null ) {
			json.color = this.color;
		}
		return json;
	}
}

export class BorderBox implements ResponsiveValue {
	constructor(
		public readonly top: BorderSide | null,
		public readonly right: BorderSide | null,
		public readonly bottom: BorderSide | null,
		public readonly left: BorderSide | null
	) {}

	get( side: Side ): BorderSide | null {
		switch ( side ) {
			case Side.Top:
				return this.top;
			case Side.Right:
				return this.right;
			case Side.Bottom:
				return this.bottom;
			case Side.Left:
				return this.left;
		}
	}

	isEmpty(): boolean {
		return SIDES.every( ( side ) => this.get( side ) === null );
	}

	/** Mirrors BorderBox::inheritFrom(): per side and part. */
	inheritFrom( larger: ResponsiveValue ): BorderBox {
		if ( ! ( larger instanceof BorderBox ) ) {
			return this;
		}
		const side = ( name: Side ): BorderSide | null => this.get( name )?.inheritFrom( larger.get( name ) ) ?? larger.get( name );
		return new BorderBox( side( Side.Top ), side( Side.Right ), side( Side.Bottom ), side( Side.Left ) );
	}

	toJSON(): Record< string, unknown > {
		const json: Record< string, unknown > = {};
		for ( const side of SIDES ) {
			const value = this.get( side );
			if ( value !== null ) {
				json[ side ] = value;
			}
		}
		return json;
	}
}
