<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A CSS color: PresetReference (theme palette) | CustomColor.
 *
 * Serialized with a `kind` tag, e.g.
 *   {"kind":"preset","preset":"color","slug":"accent"}
 *   {"kind":"custom","value":"#3858e9"}
 */
interface Color extends CssValue, ResponsiveValue
{
}
