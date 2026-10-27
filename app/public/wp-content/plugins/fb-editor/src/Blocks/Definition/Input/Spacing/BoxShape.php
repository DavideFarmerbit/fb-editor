<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Spacing;

use Fb\TypeScript\ExportToTypeScript;

/**
 * What the four parts of a box input stand for: sides (padding, margin,
 * border width, inset) or corners (radius: top-left, top-right,
 * bottom-right, bottom-left). Only changes how the editor labels them.
 */
#[ExportToTypeScript]
enum BoxShape: string
{
	case Sides   = 'sides';
	case Corners = 'corners';
}
