/**
 * Mirrors Fb\Blocks\Definition\Input\GridColumns\GridColumnsInput.
 */
import { InvalidJson, type JsonValue } from '../../../../Json/json';
import { GridColumnCount, GridColumnFit, type GridColumns } from '../../../../Values/grid-columns';
import { CustomLength, lengthFromJson } from '../../../../Values/length';

export class GridColumnsInput {
	readonly kind = 'grid-columns';

	constructor( public readonly maxCount: number ) {}

	parse( json: JsonValue ): GridColumns {
		const kind = json.get( 'kind' ).string();
		switch ( kind ) {
			case 'count': {
				const columns = new GridColumnCount( json.get( 'count' ).int() );
				if ( columns.count > this.maxCount ) {
					throw new InvalidJson( `${ json.path }: more than ${ this.maxCount } columns` );
				}
				return columns;
			}
			case 'fit': {
				const min = lengthFromJson( json.get( 'min' ) );
				if ( ! ( min instanceof CustomLength ) ) {
					throw new InvalidJson( `${ json.path }.min: expected a custom length` );
				}
				return new GridColumnFit( min );
			}
			default:
				throw new InvalidJson( `${ json.path }.kind: unknown grid columns kind "${ kind }"` );
		}
	}

	static fromJson( json: JsonValue ): GridColumnsInput {
		return new GridColumnsInput( json.get( 'maxCount' ).int() );
	}
}
