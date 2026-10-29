<?php

declare(strict_types=1);

namespace Fb\Actions\Library\IsOpen;

use Fb\Actions\Catalog\Condition;
use Fb\Actions\Catalog\Param;
use Fb\Blocks\Definition\Input\Toggle\ToggleInput;

/**
 * The target is open (or, with "open" off, closed): opened by an Open /
 * close effect, or natively ([open] on <details>, <dialog>).
 */
final class IsOpen extends Condition
{
	public const string OPEN = 'open';

	public function name(): string
	{
		return 'is-open';
	}

	public function label(): string
	{
		return __( 'Is open', 'fb-editor' );
	}

	public function params(): array
	{
		return [ Param::target(), new Param( self::OPEN, __( 'Open', 'fb-editor' ), new ToggleInput( fallback: true ) ) ];
	}
}
