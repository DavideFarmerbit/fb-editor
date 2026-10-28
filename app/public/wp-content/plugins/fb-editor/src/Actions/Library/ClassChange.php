<?php

declare(strict_types=1);

namespace Fb\Actions\Library;

use Fb\Actions\Effect;
use Fb\Actions\Param;
use Fb\Blocks\Definition\Input\ClassName\ClassNameInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;

/**
 * Adds, removes or toggles a class on the target: for the theme's CSS, or
 * as a mark later rules check (Has class).
 */
final class ClassChange extends Effect
{
	public const string MODE       = 'mode';
	public const string CLASS_NAME = 'className';

	public function name(): string
	{
		return 'class';
	}

	public function label(): string
	{
		return __( 'Class', 'fb-editor' );
	}

	public function params(): array
	{
		return [
			new Param( self::MODE, __( 'Do', 'fb-editor' ), new SelectInput( ClassMode::class, ClassMode::Toggle ) ),
			new Param( self::CLASS_NAME, __( 'Class', 'fb-editor' ), new ClassNameInput(), required: true ),
			Param::target(),
		];
	}
}
