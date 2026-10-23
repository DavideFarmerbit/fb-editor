<?php

declare(strict_types=1);

namespace Fb\Output;

interface StylePolicy
{
	public function decide( string $handle ): StyleAction;
}
