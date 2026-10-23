<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * Formats numbers the way CSS expects: no exponent, no trailing zeros,
 * no "-0", at most 4 decimals.
 */
final class CssNumber
{
	public static function format( float $number ): string
	{
		$formatted = rtrim( rtrim( number_format( $number, 4, '.', '' ), '0' ), '.' );

		return '-0' === $formatted ? '0' : $formatted;
	}
}
