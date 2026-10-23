<?php

declare(strict_types=1);

namespace Fb;

/**
 * A service that attaches itself to WordPress hooks.
 */
interface Hookable
{
	public function register(): void;
}
