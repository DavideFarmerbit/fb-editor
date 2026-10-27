/**
 * Mirrors Fb\Blocks\Definition\Input\Number\NumberInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { NumberValue } from '../../../../Values/number';

export class NumberInput {
	readonly kind = 'number';

	constructor(
		public readonly min: number | null,
		public readonly max: number | null,
		/** Step of the editor's arrows. */
		public readonly step: number,
		public readonly integer: boolean
	) {}

	allows( number: NumberValue ): boolean {
		return (
			( this.min === null || number.value >= this.min ) &&
			( this.max === null || number.value <= this.max ) &&
			( ! this.integer || Number.isInteger( number.value ) )
		);
	}

	parse( json: JsonValue ): NumberValue {
		const number = new NumberValue( json.number() );
		if ( ! this.allows( number ) ) {
			throw new InvalidJson( `${ json.path }: number not allowed by this input` );
		}
		return number;
	}

	static fromJson( json: JsonValue ): NumberInput {
		const bound = ( key: string ): number | null => json.get( key ).nullable( ( value ) => value.number() );
		return new NumberInput( bound( 'min' ), bound( 'max' ), json.get( 'step' ).number(), json.get( 'integer' ).boolean() );
	}
}
