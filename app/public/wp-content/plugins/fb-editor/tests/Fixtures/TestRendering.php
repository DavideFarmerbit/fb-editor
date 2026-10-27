<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Block;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\BlockExtensions;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Blocks\Rendering\BlockStyler;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Blocks\Variants\VariantsRepository;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Theme\ThemeConfigFile;

/**
 * The rendering services wired as in Plugin::boot(), against a theme file
 * and a fake theme (never the site's real saved defaults or variants).
 */
final readonly class TestRendering
{
	public const string THEME = 'fb-phpunit-theme';

	public DefaultsRepository $defaults;
	public VariantsRepository $variants;
	public InstanceStyleCollector $collector;
	public BlockStyler $styler;
	public BlockRenderer $renderer;

	public function __construct( BreakpointConfig $breakpoints, ?string $themeFile = null )
	{
		$file            = new ThemeConfigFile( $themeFile ?? __DIR__ . '/none.json' );
		$this->defaults  = new DefaultsRepository( $file, $breakpoints, self::THEME );
		$this->variants  = new VariantsRepository( $file, $breakpoints, self::THEME );
		$this->collector = new InstanceStyleCollector( $breakpoints, $this->defaults );
		$this->styler    = new BlockStyler( $breakpoints, $this->collector, $this->variants );
		$this->renderer  = new BlockRenderer( $this->styler, $this->defaults );
	}

	/**
	 * Renders like BlockRegistry does: with the block's complete definition.
	 *
	 * @param array<mixed> $attributes
	 */
	public function render( Block $block, array $attributes, string $content ): string
	{
		return $this->renderer->render( $block, BlockExtensions::complete( $block->definition() ), $attributes, $content );
	}
}
