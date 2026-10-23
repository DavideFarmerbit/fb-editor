/**
 * Mirrors Fb\Values\BoxValue. Sides come from Side.php (Side.generated.ts).
 */
import type { Length } from './length';
import { Side } from './Side.generated';
import type { ResponsiveValue } from './value';

export { Side };

/** In PHP declaration order: top, right, bottom, left. */
export const SIDES: readonly Side[] = Object.values( Side );

export class BoxValue implements ResponsiveValue {
	constructor(
		public readonly top: Length | null = null,
		public readonly right: Length | null = null,
		public readonly bottom: Length | null = null,
		public readonly left: Length | null = null
	) {}

	static all( length: Length ): BoxValue {
		return new BoxValue( length, length, length, length );
	}

	get( side: Side ): Length | null {
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

	inheritFrom( larger: ResponsiveValue ): BoxValue {
		if ( ! ( larger instanceof BoxValue ) ) {
			return this;
		}
		return new BoxValue(
			this.top ?? larger.top,
			this.right ?? larger.right,
			this.bottom ?? larger.bottom,
			this.left ?? larger.left
		);
	}

	/** Only set sides are written. */
	toJSON(): Partial< Record< Side, Length > > {
		const json: Partial< Record< Side, Length > > = {};
		for ( const side of SIDES ) {
			const length = this.get( side );
			if ( length !== null ) {
				json[ side ] = length;
			}
		}
		return json;
	}
}
