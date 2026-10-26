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
use Fb\CoreBlocks\Library\Button\Button;
use Fb\CoreBlocks\Library\Buttons\Buttons;
use Fb\CoreBlocks\Library\Heading\Heading;
use Fb\CoreBlocks\Library\Image\Image;
use Fb\CoreBlocks\Library\ListBlock\ListBlock;
use Fb\CoreBlocks\Library\Paragraph\Paragraph;
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
			[ new Section() ],
			[ new Paragraph(), new Heading(), new ListBlock(), new Image(), new Buttons(), new Button() ],
		);

		$services = [
			new CoreDefaultsStripper(),
			$blocks,
			new BlockDefaultsPostType( $blocks, $breakpoints ),
			new EditorAssets( $this->paths, $breakpoints, $blocks, $presets, $defaults ),
			new CanvasStyles( $tokens, $blocks ),
			new FrontendStyles( $tokens, $instances, new DefaultsStylesheet( $defaults, $breakpoints ), $blocks ),
			new BlockMarkupCleaner( $blocks, $breakpoints, $instances ),
			new CoreBlockRestrictions( $blocks ),
		];

		foreach ( $services as $service ) {
			$service->register();
		}
	}
}
