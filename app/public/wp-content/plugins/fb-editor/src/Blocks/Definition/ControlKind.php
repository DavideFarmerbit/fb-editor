<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

use Fb\TypeScript\ExportToTypeScript;

/**
 * What a control is about, for the sidebar's Settings / Styles switches.
 * Declared, not derived: a background video writes no CSS but is part of
 * the look.
 */
#[ExportToTypeScript]
enum ControlKind: string
{
	/** How the block looks (most controls). */
	case Style = 'style';
	/**
	 * What the block is, says or does: its element, its content, its
	 * accessible name, its link, where it exists (Hide on).
	 */
	case Setting = 'setting';
}
