<?php

declare(strict_types=1);

namespace Fb\Actions;

/**
 * How a closed target is hidden until opened.
 */
enum TargetHiding
{
	/** Out of the page (display: none). */
	case Removed;
	/** Invisible, keeping its space (visibility: hidden). */
	case KeepsLayout;
}
