/**
 * Reads what was typed into a unit input: "10%" means 10 with the % unit,
 * "2rem" switches to rem, "auto" picks auto. Pure logic, no React.
 */

export class TypedLength {
	constructor(
		/** The number part as typed (may be partial: "-", "1."). */
		public readonly text: string,
		/** A unit typed after the number, if it's one of the allowed units. */
		public readonly unit: string | null,
		/** "auto" was typed (and is allowed). */
		public readonly auto: boolean
	) {}
}

const NUMBER_THEN_SUFFIX = /^\s*(-?\d*\.?\d*)\s*([a-z%]+)\s*$/i;

export function parseTypedLength( typed: string, units: readonly string[], allowAuto: boolean ): TypedLength {
	if ( allowAuto && typed.trim().toLowerCase() === 'auto' ) {
		return new TypedLength( '', null, true );
	}

	const match = NUMBER_THEN_SUFFIX.exec( typed );
	const suffix = match?.[ 2 ]?.toLowerCase();
	const unit = suffix === undefined ? undefined : units.find( ( candidate ) => candidate.toLowerCase() === suffix );

	// A recognised unit is taken out of the text and selected; anything else
	// (a half-typed "re" on the way to "rem") stays as typed.
	return unit === undefined ? new TypedLength( typed, null, false ) : new TypedLength( match?.[ 1 ] ?? '', unit, false );
}
