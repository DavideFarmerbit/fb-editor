<?php

declare(strict_types=1);

namespace Fb\Actions\Catalog;

use Fb\Actions\Rendering\TargetHiding;
use Fb\Actions\Rules\ParamValues;

/**
 * What a rule does (open a panel, add a class…), in order.
 */
abstract class Effect extends ActionType
{
	final public function kind(): ActionKind
	{
		return ActionKind::Effect;
	}

	/**
	 * Markup added to the block when rendered (inside its root, first),
	 * for effects that need no script; null: done by the runner on the page.
	 * `$text`: the block's own text, e.g. for an accessible name.
	 */
	public function markup( ParamValues $params, string $text ): ?string
	{
		return null;
	}

	/** Whether the page script runs it (false: done entirely by markup()). */
	public function runsOnPage(): bool
	{
		return true;
	}

	/**
	 * Whether the effect's target starts hidden until opened by it (decided
	 * when rendering, so nothing flashes before the script runs).
	 */
	public function hidesTarget( ParamValues $params ): ?TargetHiding
	{
		return null;
	}
}
