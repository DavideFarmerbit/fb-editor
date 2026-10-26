<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

use Fb\Adapter\WpInternals;

/**
 * The core block stylesheets fb keeps: every registered core block's own
 * CSS, except blocks fb manages (their styling is ours). The same set the
 * frontend ends up loading, for the editor canvas.
 */
final readonly class CoreBlockStylesheets
{
	/**
	 * @return list<string> Stylesheet URLs.
	 */
	public function unmanagedUrls(): array
	{
		$urls = [];
		foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $ignored ) {
			if ( ! str_starts_with( $name, 'core/' ) || null !== ManagedCoreBlock::tryFrom( $name ) ) {
				continue;
			}
			$url = WpInternals::coreBlockStylesheetUrl( substr( $name, strlen( 'core/' ) ) );
			if ( null !== $url ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}
}
