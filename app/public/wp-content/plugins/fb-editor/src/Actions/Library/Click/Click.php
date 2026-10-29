<?php

declare(strict_types=1);

namespace Fb\Actions\Library\Click;

use Fb\Actions\Catalog\Trigger;

/**
 * A click or tap on the block, or Enter / Space while it has keyboard focus
 * (the runner makes it focusable). Clicks on links and buttons inside it
 * stay theirs.
 */
final class Click extends Trigger
{
	public function name(): string
	{
		return 'click';
	}

	public function label(): string
	{
		return __( 'Click', 'fb-editor' );
	}
}
