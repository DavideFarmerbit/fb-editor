<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

enum Scope: string
{
	/** One value per breakpoint (desktop-first inheritance). */
	case Responsive = 'responsive';
	/** One value for all breakpoints (e.g. the HTML tag). */
	case Static = 'static';
}
