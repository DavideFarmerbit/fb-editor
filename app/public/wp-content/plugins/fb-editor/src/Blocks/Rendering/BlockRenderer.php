<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Block;
use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Output\ClassList;

/**
 * Bridge from WordPress's render callback to Block::render(): styles the
 * block from its raw attributes (the boundary) and builds the context.
 */
final readonly class BlockRenderer
{
	public function __construct(
		private BlockStyler $styler,
		private DefaultsRepository $defaults,
	) {
	}

	/**
	 * @param array<mixed> $attributes Raw block attributes from WordPress.
	 */
	public function render( Block $block, array $attributes, string $content ): string
	{
		$definition = $block->definition();
		$styled     = $this->styler->style( $definition, $attributes[ BlockRegistry::ATTRIBUTE ] ?? null );

		$classes     = $styled->classes;
		$userClasses = $attributes['className'] ?? null;
		if ( is_string( $userClasses ) ) {
			$classes = $classes->with( ClassList::parse( $userClasses ) );
		}

		return $block->render(
			new RenderContext( $definition, $styled->attribute->values, $styled->variant?->values, $this->defaults->for( $definition ), $classes, $content )
		);
	}
}
