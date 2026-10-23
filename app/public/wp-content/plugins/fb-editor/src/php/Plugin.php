<?php

declare(strict_types=1);

namespace Fb;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Library\Section\Section;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Blocks\Rendering\InstanceStyleCollector;
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
		$presets     = new PresetRepository();
		$tokens      = new TokenStylesheet( $presets );
		$instances   = new InstanceStyleCollector( $breakpoints );
		$blocks      = new BlockRegistry(
			new BlockRenderer( $breakpoints, $instances ),
			new Section(),
		);

		$services = [
			new CoreDefaultsStripper(),
			$blocks,
			new EditorAssets( $this->paths, $breakpoints, $blocks, $presets ),
			new CanvasStyles( $tokens ),
			new FrontendStyles( $tokens, $instances ),
			new BlockMarkupCleaner(),
		];

		foreach ( $services as $service ) {
			$service->register();
		}
	}
}
