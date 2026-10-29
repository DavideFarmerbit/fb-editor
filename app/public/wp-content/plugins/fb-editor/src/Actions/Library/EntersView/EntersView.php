<?php

declare(strict_types=1);

namespace Fb\Actions\Library\EntersView;

use Fb\Actions\Catalog\Param;
use Fb\Actions\Catalog\Trigger;
use Fb\Blocks\Definition\Input\Toggle\ToggleInput;

/**
 * The block scrolls into view: the first time only, or every time.
 */
final class EntersView extends Trigger
{
	public const string EVERY_TIME = 'everyTime';

	public function name(): string
	{
		return 'enters-view';
	}

	public function label(): string
	{
		return __( 'Enters view', 'fb-editor' );
	}

	public function params(): array
	{
		return [ new Param( self::EVERY_TIME, __( 'Every time', 'fb-editor' ), new ToggleInput() ) ];
	}
}
