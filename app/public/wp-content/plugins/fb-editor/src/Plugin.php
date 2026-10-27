<?php

declare(strict_types=1);

namespace Fb;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Defaults\BlockDefaultsPostType;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Defaults\DefaultsStylesheet;
use Fb\Blocks\Rendering\BlockStyler;
use Fb\Blocks\Variants\BlockVariantsPostType;
use Fb\Blocks\Variants\VariantsRepository;
use Fb\Blocks\Variants\VariantsStylesheet;
use Fb\Blocks\Library\Container\Container;
use Fb\Blocks\Library\Icon\Icon;
use Fb\Blocks\Library\Separator\Separator;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Breakpoints\BreakpointConfig;
use Fb\CoreBlocks\CoreBlockRestrictions;
use Fb\CoreBlocks\Library\Button\Button;
use Fb\CoreBlocks\Library\Buttons\Buttons;
use Fb\CoreBlocks\Library\Heading\Heading;
use Fb\CoreBlocks\Library\Image\Image;
use Fb\CoreBlocks\Library\ListBlock\ListBlock;
use Fb\CoreBlocks\Library\ListItem\ListItem;
use Fb\CoreBlocks\Library\Paragraph\Paragraph;
use Fb\Editor\CanvasStyles;
use Fb\Editor\EditorAssets;
use Fb\Output\BlockMarkupCleaner;
use Fb\Output\FrontendStyles;
use Fb\Output\TokenStylesheet;
use Fb\Presets\PresetRepository;
use Fb\Site\AdminSidebar\AdminSidebar;
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
		$variants    = new VariantsRepository( $themeFile, $breakpoints );
		$instances   = new InstanceStyleCollector( $breakpoints, $defaults );
		$styler      = new BlockStyler( $breakpoints, $instances, $variants, $defaults );
		$blocks      = new BlockRegistry(
			new BlockRenderer( $styler, $defaults ),
			[ new Container(), new Icon(), new Separator() ],
			[ new Paragraph(), new Heading(), new ListBlock(), new ListItem(), new Image(), new Buttons(), new Button() ],
		);

		$services = [
			new CoreDefaultsStripper(),
			$blocks,
			new BlockDefaultsPostType( $blocks, $breakpoints ),
			new BlockVariantsPostType( $blocks, $breakpoints ),
			new EditorAssets( $this->paths, $breakpoints, $blocks, $presets, $defaults, $variants ),
			new CanvasStyles( $tokens, $blocks, $breakpoints ),
			new FrontendStyles(
				$tokens,
				$instances,
				new DefaultsStylesheet( $defaults, $breakpoints ),
				new VariantsStylesheet( $defaults, $breakpoints ),
				$blocks,
				$breakpoints
			),
			new BlockMarkupCleaner( $blocks, $styler ),
			new CoreBlockRestrictions( $blocks ),
			new AdminSidebar( $this->paths ),
		];

		foreach ( $services as $service ) {
			$service->register();
		}
	}
}
