<?php

declare(strict_types=1);

namespace Fb\Actions\Rules;

use Fb\TypeScript\ExportToTypeScript;

/**
 * When a rule's effects are reversed on their own (closing what it opened,
 * removing the class it added).
 */
#[ExportToTypeScript]
enum Undo: string
{
	case Never   = 'never';
	/** The pointer leaves the block (or keyboard focus does): for hover rules. */
	case Leave   = 'leave';
	/** A click outside the block and its targets: for menus and popovers. */
	case Outside = 'outside';
}
