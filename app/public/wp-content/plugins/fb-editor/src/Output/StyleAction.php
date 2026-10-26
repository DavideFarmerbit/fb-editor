<?php

declare(strict_types=1);

namespace Fb\Output;

enum StyleAction
{
	/** Not printed at all. */
	case Remove;
	/** Printed inside the lowest cascade layer (`wp`). */
	case Layer;
	/** Left untouched (not core, not ours to judge). */
	case Keep;
	/**
	 * Core's bundled block library (all core blocks' CSS in one file, as
	 * wp-admin loads it): printed instead as the separate stylesheets of the
	 * core blocks fb doesn't manage, each in the `wp` layer, i.e. what the
	 * frontend loads. Anything else under that handle is removed.
	 */
	case Unbundle;
}
