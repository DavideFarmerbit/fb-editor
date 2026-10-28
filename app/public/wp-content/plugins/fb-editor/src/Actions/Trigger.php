<?php

declare(strict_types=1);

namespace Fb\Actions;

/**
 * When a rule fires (click, entering the view…). Any of a rule's triggers
 * fires it.
 */
abstract class Trigger extends ActionType
{
	final public function kind(): ActionKind
	{
		return ActionKind::Trigger;
	}
}
