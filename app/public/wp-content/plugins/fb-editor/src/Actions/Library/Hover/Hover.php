<?php

declare(strict_types=1);

namespace Fb\Actions\Library\Hover;

use Fb\Actions\Catalog\Trigger;

/**
 * The pointer enters the block, or keyboard focus does. With undo "leave",
 * leaving reverses the effects.
 */
final class Hover extends Trigger
{
	public function name(): string
	{
		return 'hover';
	}

	public function label(): string
	{
		return __( 'Hover', 'fb-editor' );
	}
}
