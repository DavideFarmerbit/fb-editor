<?php

declare(strict_types=1);

namespace Fb\Actions\Library\HasClass;

use Fb\Actions\Catalog\Condition;
use Fb\Actions\Catalog\Param;
use Fb\Blocks\Definition\Input\ClassName\ClassNameInput;
use Fb\Blocks\Definition\Input\Toggle\ToggleInput;

/**
 * The target has a class (or, with "has" off, doesn't): e.g. one an earlier
 * rule added, to chain rules through the page's state.
 */
final class HasClass extends Condition
{
	public const string CLASS_NAME = 'className';
	public const string HAS        = 'has';

	public function name(): string
	{
		return 'has-class';
	}

	public function label(): string
	{
		return __( 'Has class', 'fb-editor' );
	}

	public function params(): array
	{
		return [
			Param::target(),
			new Param( self::CLASS_NAME, __( 'Class', 'fb-editor' ), new ClassNameInput(), required: true ),
			new Param( self::HAS, __( 'Has it', 'fb-editor' ), new ToggleInput( fallback: true ) ),
		];
	}
}
