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
}
