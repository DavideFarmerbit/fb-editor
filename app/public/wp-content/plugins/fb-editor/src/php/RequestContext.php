<?php

declare(strict_types=1);

namespace Fb;

/**
 * What kind of request is being served.
 *
 * Must be resolved lazily (inside hook callbacks): REST requests are only
 * identified during request parsing, long after plugins load.
 */
enum RequestContext
{
	case Frontend;
	case Admin;
	case Rest;

	public static function current(): self
	{
		return match ( true ) {
			wp_is_serving_rest_request() => self::Rest,
			is_admin()                   => self::Admin,
			default                      => self::Frontend,
		};
	}

	/** Requests that may build the editor canvas (iframe) assets. */
	public function buildsEditorCanvas(): bool
	{
		return self::Frontend !== $this;
	}
}
