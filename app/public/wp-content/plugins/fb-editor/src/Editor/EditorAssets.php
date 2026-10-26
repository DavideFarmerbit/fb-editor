<?php

declare(strict_types=1);

namespace Fb\Editor;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Storage\BlockDesignInfo;
use Fb\Blocks\Storage\ThemeDesignPostType;
use Fb\Blocks\Variants\VariantsRepository;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Hookable;
use Fb\Presets\PresetRepository;
use Fb\PluginPaths;

/**
 * Editor UI assets (outer editor frame, not the canvas iframe).
 */
final readonly class EditorAssets implements Hookable
{
	private const string HANDLE = 'fb-editor';

	public function __construct(
		private PluginPaths $paths,
		private BreakpointConfig $breakpoints,
		private BlockRegistry $blocks,
		private PresetRepository $presets,
		private DefaultsRepository $defaults,
		private VariantsRepository $variants,
	) {
	}

	public function register(): void
	{
		add_action( 'enqueue_block_editor_assets', $this->enqueue( ... ) );
	}

	private function enqueue(): void
	{
		$asset = $this->paths->buildAsset( 'editor' );

		if ( null === $asset ) {
			return;
		}

		wp_enqueue_script( self::HANDLE, $this->paths->url( 'build/editor.js' ), $asset->dependencies, $asset->version, true );

		$definitions = $this->blocks->definitions();

		$settings = new EditorSettings(
			$this->breakpoints->breakpoints(),
			$this->presets->all(),
			$definitions,
			array_map(
				fn ( BlockDefinition $definition ): BlockDesignInfo => new BlockDesignInfo(
					$definition->name,
					ThemeDesignPostType::slug( $definition->name, get_stylesheet() ),
					$this->defaults->theme( $definition ),
					$this->variants->theme( $definition )
				),
				$definitions
			)
		);

		wp_add_inline_script( self::HANDLE, 'window.fbEditorSettings = ' . wp_json_encode( $settings ) . ';', 'before' );

		if ( is_readable( $this->paths->path( 'build/editor.css' ) ) ) {
			wp_enqueue_style( self::HANDLE, $this->paths->url( 'build/editor.css' ), [ 'wp-components' ], $asset->version );
		}
	}
}
