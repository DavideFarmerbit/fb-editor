<?php

declare(strict_types=1);

namespace Fb\Actions\Library;

use Fb\Actions\Catalog\ActionCatalog;
use Fb\Actions\Library\ClassChange\ClassChange;
use Fb\Actions\Library\Click\Click;
use Fb\Actions\Library\EntersView\EntersView;
use Fb\Actions\Library\HasClass\HasClass;
use Fb\Actions\Library\Hover\Hover;
use Fb\Actions\Library\IsOpen\IsOpen;
use Fb\Actions\Library\Link\Link;
use Fb\Actions\Library\OpenClose\OpenClose;
use Fb\Actions\Library\PageLoad\PageLoad;
use Fb\Actions\Library\ScrollTo\ScrollTo;

/**
 * The plugin's own triggers, conditions and effects. A new type: its own
 * folder here (PHP class + TS runner), listed below and in Runtime/actions.ts.
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
