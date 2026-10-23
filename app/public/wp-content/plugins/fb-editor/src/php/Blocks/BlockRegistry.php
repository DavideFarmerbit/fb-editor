<?php

declare(strict_types=1);

namespace Fb\Blocks;

use Fb\Blocks\Rendering\BlockRenderer;
use Fb\Hookable;

/**
 * Registers fb blocks with WordPress.
 *
 * Only a single `fb` attribute is declared: all control values live there.
 * Rendering is server-side; the editor script adds `edit`/`save` on top of
 * the metadata WordPress already bootstraps for server-registered blocks.
 */
final readonly class BlockRegistry implements Hookable
{
	/** @var list<Block> */
	public array $blocks;

	public function __construct(
		private BlockRenderer $renderer,
		Block ...$blocks,
	) {
		$this->blocks = array_values( $blocks );
	}

	public function register(): void
	{
		add_action( 'init', $this->registerBlocks( ... ) );
	}

	private function registerBlocks(): void
	{
		foreach ( $this->blocks as $block ) {
			$definition = $block->definition();

			register_block_type(
				(string) $definition->name,
				[
					'api_version'     => '3',
					'title'           => $definition->title,
					'category'        => 'design',
					'attributes'      => [
						'fb' => [ 'type' => 'object', 'default' => new \stdClass() ],
					],
					'supports'        => [
						'html'            => false,
						'className'       => true,
						'customClassName' => true,
					],
					'render_callback' => fn ( array $attributes, string $content ): string => $this->renderer->render( $block, $attributes, $content ),
				]
			);
		}
	}
}
