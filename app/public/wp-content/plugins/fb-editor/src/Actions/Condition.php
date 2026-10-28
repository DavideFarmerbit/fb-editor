<?php

declare(strict_types=1);

namespace Fb\Actions;

/**
 * Whether a fired rule may run (the target is open, has a class…): every
 * condition of a rule must hold.
 */
abstract class Condition extends ActionType
{
	final public function kind(): ActionKind
	{
		return ActionKind::Condition;
	}
}
