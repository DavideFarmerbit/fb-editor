<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * How a grid makes its columns (one `grid-template-columns` value):
 * GridColumnCount (n equal columns) or GridColumnFit (as many as fit, each
 * at least a width). JSON tagged by `kind`; see GridColumnsJson.
 */
interface GridColumns extends CssValue, ResponsiveValue
{
}
