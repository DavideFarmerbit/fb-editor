<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Core blocks freed from their `parent` restriction: insertable anywhere,
 * not only inside the block core ties them to. A Button needs no Buttons
 * around it: its markup and fb's CSS don't depend on it, and a flex
 * Container lays buttons out just as well.
 */
#[ExportToTypeScript]
enum FreedCoreBlock: string
{
	case Button = 'core/button';
}
