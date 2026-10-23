<?php

declare(strict_types=1);

namespace Fb\Editor;

use Fb\Blocks\Block;
use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Hookable;
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

		$settings = new EditorSettings(
			$this->breakpoints->breakpoints(),
			...array_map( static fn ( Block $block ): BlockDefinition => $block->definition(), $this->blocks->blocks )
		);

		wp_add_inline_script( self::HANDLE, 'window.fbEditorSettings = ' . wp_json_encode( $settings ) . ';', 'before' );

		if ( is_readable( $this->paths->path( 'build/editor.css' ) ) ) {
			wp_enqueue_style( self::HANDLE, $this->paths->url( 'build/editor.css' ), [ 'wp-components' ], $asset->version );
		}
	}
}
