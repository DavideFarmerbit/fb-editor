/**
 * Mirrors Fb\Blocks\Definition\Input\Select\SelectInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { KeywordValue } from '../../../../Values/keyword';

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

	static fromJson( json: JsonValue ): SelectInput {
		return new SelectInput(
			json
				.get( 'options' )
				.list()
				.map( ( option ) => new SelectOption( option.get( 'value' ).scalar(), option.get( 'label' ).string() ) ),
			json.get( 'fallback' ).nullable( ( fallback ) => fallback.scalar() )
		);
	}
}
