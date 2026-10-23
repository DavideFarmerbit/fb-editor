/**
 * Mirrors Fb\Json\JsonValue: typed, read-only view over decoded JSON. Shape
 * mismatches throw InvalidJson with the exact path.
 */
export class InvalidJson extends Error {}

export class JsonValue {
	private constructor(
		private readonly value: unknown,
		public readonly path: string
	) {}

	static of( decoded: unknown, source: string ): JsonValue {
		return new JsonValue( decoded, source );
	}

	isNull(): boolean {
		return this.value === null || this.value === undefined;
	}

	isObject(): boolean {
		return typeof this.value === 'object' && this.value !== null && ! Array.isArray( this.value );
	}

	has( key: string ): boolean {
		return this.isObject() && Object.prototype.hasOwnProperty.call( this.value, key );
	}

	get( key: string ): JsonValue {
		if ( ! this.isObject() ) {
			throw new InvalidJson( `${ this.path }: expected an object` );
		}
		if ( ! this.has( key ) ) {
			throw new InvalidJson( `${ this.path }.${ key }: missing` );
		}
		return new JsonValue( ( this.value as Record< string, unknown > )[ key ], `${ this.path }.${ key }` );
	}

	/** Object members in document order. */
	properties(): Array< [ string, JsonValue ] > {
		if ( ! this.isObject() ) {
			throw new InvalidJson( `${ this.path }: expected an object` );
		}
		return Object.entries( this.value as Record< string, unknown > ).map( ( [ key, child ] ) => [
			key,
			new JsonValue( child, `${ this.path }.${ key }` ),
		] );
	}

	string(): string {
		if ( typeof this.value !== 'string' ) {
			throw new InvalidJson( `${ this.path }: expected a string` );
		}
		return this.value;
	}

	int(): number {
		if ( ! Number.isInteger( this.value ) ) {
			throw new InvalidJson( `${ this.path }: expected an integer` );
		}
		return this.value as number;
	}

	number(): number {
		if ( typeof this.value !== 'number' || ! Number.isFinite( this.value ) ) {
			throw new InvalidJson( `${ this.path }: expected a finite number` );
		}
		return this.value;
	}

	/** A string that must be one of a (generated) string enum's values. */
	oneOf< E extends Record< string, string > >( values: E ): E[ keyof E ] {
		const value = this.string();
		const match = ( Object.values( values ) as Array< E[ keyof E ] > ).find( ( candidate ) => candidate === value );
		if ( match === undefined ) {
			throw new InvalidJson( `${ this.path }: "${ value }" is not one of ${ Object.values( values ).join( ', ' ) }` );
		}
		return match;
	}

	/** A string, or an integer as its string form (PHP enum option values). */
	scalar(): string {
		if ( typeof this.value === 'string' ) {
			return this.value;
		}
		if ( Number.isInteger( this.value ) ) {
			return String( this.value );
		}
		throw new InvalidJson( `${ this.path }: expected a string or an integer` );
	}

	boolean(): boolean {
		if ( typeof this.value !== 'boolean' ) {
			throw new InvalidJson( `${ this.path }: expected a boolean` );
		}
		return this.value;
	}

	list(): JsonValue[] {
		if ( ! Array.isArray( this.value ) ) {
			throw new InvalidJson( `${ this.path }: expected an array` );
		}
		return this.value.map( ( item: unknown, index ) => new JsonValue( item, `${ this.path }[${ index }]` ) );
	}

	/**
	 * The raw decoded data, to hand to another boundary parser (e.g. block
	 * attributes to BlockValues). Never use it to read fields directly.
	 */
	decoded(): unknown {
		return this.value;
	}

	nullable< T >( parse: ( json: JsonValue ) => T ): T | null {
		return this.isNull() ? null : parse( this );
	}
}
