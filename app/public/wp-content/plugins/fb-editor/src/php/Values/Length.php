<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A CSS length: PresetLength | CustomLength | AutoLength.
 *
 * Serialized with a `kind` tag, e.g.
 *   {"kind":"preset","preset":"spacing","slug":"40"}
 *   {"kind":"custom","value":-12.5,"unit":"px"}
 *   {"kind":"auto"}
 */
interface Length extends CssValue, ResponsiveValue
{
}
