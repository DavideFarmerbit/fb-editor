/**
 * Mirrors Fb\Blocks\Values\BlockBindings: bindings grouped by look; looks
 * without states in a base layer, with states in its state layer, each in
 * cascade order; conditions wrap a look in their media query.
 */
import type { StyleBinding, StyleCompiler } from '../../Styles/compiler';
import { statesLayer, wrapInLayer, type CssLayer } from '../../Styles/css';
import { LookKey } from '../Looks/look-key';

class LookBindings {
	constructor(
		public readonly key: LookKey,
		/** The look's media query, if it has conditions (from the definition). */
		public readonly mediaQuery: string | null,
		public readonly bindings: readonly StyleBinding[]
	) {}
}

export class BlockBindings {
	constructor( private readonly looks: ReadonlyMap< string, LookBindings > = new Map() ) {}

	/** Mirrors BlockBindings::with(). */
	with( key: LookKey, mediaQuery: string | null, bindings: readonly StyleBinding[] ): BlockBindings {
		if ( bindings.length === 0 ) {
			return this;
		}
		const looks = new Map( this.looks );
		const id = key.id();
		looks.set( id, new LookBindings( key, mediaQuery, [ ...( looks.get( id )?.bindings ?? [] ), ...bindings ] ) );
		return new BlockBindings( looks );
	}

	merge( other: BlockBindings ): BlockBindings {
		let merged: BlockBindings = this;
		other.looks.forEach( ( look ) => {
			merged = merged.with( look.key, look.mediaQuery, look.bindings );
		} );
		return merged;
	}

	/** `layer`: the base layer (defaults, variants or instances). */
	css( compiler: StyleCompiler, layer: CssLayer ): string {
		let base = '';
		let states = '';
		[ ...this.looks.values() ]
			.sort( ( a, b ) => LookKey.compare( a.key, b.key ) )
			.forEach( ( look ) => {
				let rules = compiler.rules( look.bindings );
				if ( rules !== '' && look.mediaQuery !== null ) {
					rules = `@media ${ look.mediaQuery }{${ rules }}`;
				}
				if ( look.key.hasStates() ) {
					states += rules;
				} else {
					base += rules;
				}
			} );
		return ( base === '' ? '' : wrapInLayer( layer, base ) ) + ( states === '' ? '' : wrapInLayer( statesLayer( layer ), states ) );
	}
}
