<?php

declare(strict_types=1);

namespace Fb\Breakpoints;

use Fb\TypeScript\ExportToTypeScript;

#[ExportToTypeScript]
enum BreakpointIcon: string
{
	case Desktop = 'desktop';
	case Tablet  = 'tablet';
	case Mobile  = 'mobile';
}
