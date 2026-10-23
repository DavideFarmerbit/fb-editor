/**
 * Mirrors Fb\Blocks\Definition\Input\*. `parse()` applies the same rules as
 * PHP, so the editor never produces a value the server would drop.
 */
import { InvalidJson, type JsonValue } from '../../../Json/json';
import { PresetKind } from '../../../Presets/presets';
import { BoxValue, Side } from '../../../Values/box';
import { KeywordValue } from '../../../Values/keyword';
import { AutoLength, CustomLength, lengthFromJson, PresetLength, type Length } from '../../../Values/length';
import { SpacingMode } from './SpacingMode.generated';

export { SpacingMode };

export class SpacingInput {
	readonly kind = 'spacing';

	constructor(
		public readonly allowAuto: boolean,
		public readonly allowNegative: boolean,
		public readonly modes: readonly SpacingMode[]
	) {}

	allows( length: Length ): boolean {
		if ( length instanceof AutoLength ) {
			return this.allowAuto;
		}
		if ( length instanceof CustomLength ) {
			return this.allowNegative || ! length.isNegative();
		}
		return length instanceof PresetLength && length.preset === PresetKind.Spacing;
	}

	/** Invalid sides are dropped one by one; a box with no valid side is rejected. */
	parse( json: JsonValue ): BoxValue {
		if ( ! json.isObject() ) {
			throw new InvalidJson( `${ json.path }: expected an object` );
		}

		const side = ( name: Side ): Length | null => {
			if ( ! json.has( name ) || json.get( name ).isNull() ) {
				return null;
			}
			try {
				const length = lengthFromJson( json.get( name ) );
				return this.allows( length ) ? length : null;
			} catch ( error ) {
				if ( error instanceof InvalidJson ) {
					return null;
				}
				throw error;
			}
		};

		const box = new BoxValue( side( Side.Top ), side( Side.Right ), side( Side.Bottom ), side( Side.Left ) );
		if ( box.isEmpty() ) {
			throw new InvalidJson( `${ json.path }: no valid side` );
		}
		return box;
	}
}

export class SelectOption {
	constructor(
		public readonly value: string,
		public readonly label: string
	) {}
}

export class SelectInput {
	readonly kind = 'select';

	constructor(
		public readonly options: readonly SelectOption[],
		/** Used while nothing is chosen (render + editor); never a style. */
		public readonly fallback: string | null
	) {}

	parse( json: JsonValue ): KeywordValue {
		const value = json.string();
		if ( ! this.options.some( ( option ) => option.value === value ) ) {
			throw new InvalidJson( `${ json.path }: "${ value }" is not an option` );
		}
		return new KeywordValue( value );
	}
}

export type Input = SpacingInput | SelectInput;

export function inputFromJson( json: JsonValue ): Input {
	const kind = json.get( 'kind' ).string();
	switch ( kind ) {
		case 'spacing':
			return new SpacingInput(
				json.get( 'allowAuto' ).boolean(),
				json.get( 'allowNegative' ).boolean(),
				json.get( 'modes' ).list().map( ( mode ) => mode.oneOf( SpacingMode ) )
			);
		case 'select':
			return new SelectInput(
				json
					.get( 'options' )
					.list()
					.map( ( option ) => new SelectOption( option.get( 'value' ).scalar(), option.get( 'label' ).string() ) ),
				json.get( 'fallback' ).nullable( ( fallback ) => fallback.scalar() )
			);
		default:
			throw new InvalidJson( `${ json.path }.kind: unknown input kind "${ kind }"` );
	}
}
