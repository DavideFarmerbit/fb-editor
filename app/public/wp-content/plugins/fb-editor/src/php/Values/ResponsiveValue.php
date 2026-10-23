<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A value that can be set per breakpoint.
 *
 * Desktop-first: a smaller breakpoint's value inherits whatever it leaves
 * unset from the next larger breakpoint (e.g. a box with only `top` set
 * keeps the larger breakpoint's other sides).
 */
interface ResponsiveValue extends \JsonSerializable
{
	/**
	 * This value, with anything it leaves unset taken from `$larger`.
	 * Values of an unrelated type are ignored.
	 */
	public function inheritFrom( self $larger ): static;
}
