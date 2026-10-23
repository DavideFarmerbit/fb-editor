<?php

declare(strict_types=1);

namespace Fb;

use Fb\Breakpoints\BreakpointConfig;
use Fb\Editor\CanvasStyles;
use Fb\Editor\EditorAssets;
use Fb\Output\BlockMarkupCleaner;
use Fb\Output\FrontendStyles;
use Fb\Output\TokenStylesheet;
use Fb\Presets\PresetRepository;
use Fb\ThemeJson\CoreDefaultsStripper;

/**
 * Composition root: builds the services and hooks them up.
 */
final readonly class Plugin
{
	public function __construct(
		private PluginPaths $paths,
	) {
	}

	public function boot(): void
	{
		$breakpoints = new BreakpointConfig();
		$tokens      = new TokenStylesheet( new PresetRepository() );

		$services = [
			new CoreDefaultsStripper(),
			new EditorAssets( $this->paths, $breakpoints ),
			new CanvasStyles( $tokens ),
			new FrontendStyles( $tokens ),
			new BlockMarkupCleaner(),
		];

		foreach ( $services as $service ) {
			$service->register();
		}
	}
}
