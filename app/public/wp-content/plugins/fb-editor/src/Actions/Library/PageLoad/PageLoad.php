<?php

declare(strict_types=1);

namespace Fb\Actions\Library\PageLoad;

use Fb\Actions\Catalog\Trigger;

/**
 * Once, when the page is ready.
 */
final class PageLoad extends Trigger
{
	public function name(): string
	{
		return 'page-load';
	}

	public function label(): string
	{
		return __( 'Page load', 'fb-editor' );
	}
}
