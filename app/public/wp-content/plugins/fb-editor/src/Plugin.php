<?php

declare(strict_types=1);

namespace Fb;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Defaults\BlockDefaultsPostType;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Defaults\DefaultsStylesheet;
use Fb\Blocks\Library\Section\Section;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Breakpoints\BreakpointConfig;
use Fb\CoreBlocks\CoreBlockRestrictions;
use Fb\Editor\CanvasStyles;
use Fb\Editor\EditorAssets;
use Fb\Output\BlockMarkupCleaner;
use Fb\Output\FrontendStyles;
use Fb\Output\TokenStylesheet;
use Fb\Presets\PresetRepository;
use Fb\Theme\ThemeConfigFile;
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
		$themeFile   = new ThemeConfigFile();
		$breakpoints = new BreakpointConfig( $themeFile );
		$defaults    = new DefaultsRepository( $themeFile, $breakpoints );
		$presets     = new PresetRepository();
		$tokens      = new TokenStylesheet( $presets );
		$instances   = new InstanceStyleCollector( $breakpoints, $defaults );
		$blocks      = new BlockRegistry(
			new BlockRenderer( $breakpoints, $instances, $defaults ),
			new Section(),
		);

		$services = [
			new CoreDefaultsStripper(),
			$blocks,
			new BlockDefaultsPostType( $blocks, $breakpoints ),
			new EditorAssets( $this->paths, $breakpoints, $blocks, $presets, $defaults ),
			new CanvasStyles( $tokens ),
			new FrontendStyles( $tokens, $instances, new DefaultsStylesheet( $defaults, $breakpoints ) ),
			new BlockMarkupCleaner(),
			new CoreBlockRestrictions( $blocks ),
		];

		foreach ( $services as $service ) {
			$service->register();
		}
	}
}
