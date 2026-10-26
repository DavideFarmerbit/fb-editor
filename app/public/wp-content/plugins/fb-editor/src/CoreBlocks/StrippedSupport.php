<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Block supports removed from every allowed core block: all of core's
 * styling UI and output. fb controls replace them. Behaviour supports
 * (anchor, className, interactivity, splitting…) stay.
 */
#[ExportToTypeScript]
enum StrippedSupport: string
{
	case Align      = 'align';
	case AlignWide  = 'alignWide';
	case Background = 'background';
	case Border     = '__experimentalBorder';
	case Color      = 'color';
	case Dimensions = 'dimensions';
	case Filter     = 'filter';
	case Layout     = 'layout';
	case Position   = 'position';
	case Shadow     = 'shadow';
	case Spacing    = 'spacing';
	case TextAlign  = 'textAlign';
	case Typography = 'typography';
}
