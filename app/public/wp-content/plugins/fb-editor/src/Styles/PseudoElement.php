<?php

declare(strict_types=1);

namespace Fb\Styles;

/**
 * Generated boxes base CSS may draw (e.g. the button icon), outside the
 * zero-specificity :where() like any pseudo-element must be.
 */
enum PseudoElement: string
{
	case Before = 'before';
	case After  = 'after';
}
