<?php

declare(strict_types=1);

namespace Fb\Actions\Library;

use Fb\Actions\ActionCatalog;

/**
 * The plugin's own triggers, conditions and effects. A new type: its class
 * here, its runner in TypeScript (listed in runtime/actions.ts).
 */
final class Library
{
	public static function catalog(): ActionCatalog
	{
		return new ActionCatalog(
			new Click(),
			new Hover(),
			new EntersView(),
			new PageLoad(),
			new IsOpen(),
			new HasClass(),
			new Link(),
			new OpenClose(),
			new ClassChange(),
			new ScrollTo(),
		);
	}
}
