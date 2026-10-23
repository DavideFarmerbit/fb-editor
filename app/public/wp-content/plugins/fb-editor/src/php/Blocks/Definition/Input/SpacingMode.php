<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input;

/**
 * How the spacing control groups sides in the UI. Values are always stored
 * per side; this is presentation only.
 */
enum SpacingMode: string
{
	/** One value for all sides. */
	case Linked = 'linked';
	/** Block axis (top/bottom) + inline axis (left/right). */
	case Axis = 'axis';
	/** Four independent sides. */
	case Sides = 'sides';
}
