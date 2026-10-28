<?php

declare(strict_types=1);

namespace Fb\Styles;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Generated boxes CSS may draw (the button icon, a block's ::before part,
 * list markers), outside the zero-specificity :where() like any
 * pseudo-element must be.
 */
#[ExportToTypeScript]
enum PseudoElement: string
{
	case Before = 'before';
	case After  = 'after';
	case Marker = 'marker';
}
