/**
 * Mirrors Fb\Values\CssNumber: no exponent, no trailing zeros, no "-0",
 * at most 4 decimals, halves rounded away from zero (like PHP's round()).
 */
export function formatCssNumber( number: number ): string {
	const scaled = Math.abs( number ) * 10000;
	const rounded = ( Math.sign( number ) * Math.round( scaled ) ) / 10000;
	const formatted = rounded.toFixed( 4 ).replace( /0+$/, '' ).replace( /\.$/, '' );

	return formatted === '-0' ? '0' : formatted;
}
