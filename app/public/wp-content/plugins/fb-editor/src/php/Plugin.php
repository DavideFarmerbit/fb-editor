<?php

declare(strict_types=1);

namespace Fb;

use Fb\Breakpoints\BreakpointConfig;
use Fb\Editor\EditorAssets;
use Fb\Output\CoreStylePolicy;
use Fb\Output\FrontendStyles;
use Fb\Presets\PresetRepository;

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
		$presets     = new PresetRepository();

		$services = [
			new EditorAssets( $this->paths, $breakpoints ),
			new FrontendStyles( new CoreStylePolicy(), $presets ),
		];

		foreach ( $services as $service ) {
			$service->register();
		}
	}
}
