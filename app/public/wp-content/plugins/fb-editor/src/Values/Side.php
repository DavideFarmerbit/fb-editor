<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\TypeScript\ExportToTypeScript;

#[ExportToTypeScript]
enum Side: string
{
	case Top    = 'top';
	case Right  = 'right';
	case Bottom = 'bottom';
	case Left   = 'left';
}
