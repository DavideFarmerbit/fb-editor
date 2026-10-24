<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Breakpoints\Breakpoint;
use Fb\Breakpoints\BreakpointId;
use Fb\Breakpoints\BreakpointList;

/**
 * A small fixed breakpoint list for tests, independent of the plugin's
 * fallback and of the theme: desktop (base) > tablet ≤1024 > mobile ≤767.
 */
final class TestBreakpoints
{
	public static function list(): BreakpointList
	{
		return new BreakpointList(
			new Breakpoint( new BreakpointId( 'desktop' ), 'Desktop' ),
			new Breakpoint( new BreakpointId( 'tablet' ), 'Tablet', 1024 ),
			new Breakpoint( new BreakpointId( 'mobile' ), 'Mobile', 767 ),
		);
	}
}
