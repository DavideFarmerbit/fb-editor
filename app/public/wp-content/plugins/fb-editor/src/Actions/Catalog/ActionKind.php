<?php

declare(strict_types=1);

namespace Fb\Actions\Catalog;

use Fb\TypeScript\ExportToTypeScript;

/**
 * The three things a rule is made of: when it fires (any trigger), whether
 * it may (every condition), what it does (each effect, in order).
 */
#[ExportToTypeScript]
enum ActionKind: string
{
	case Trigger   = 'trigger';
	case Condition = 'condition';
	case Effect    = 'effect';
}
