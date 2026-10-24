<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Block;
use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Values\BlockValues;
use Fb\Blocks\Values\InstanceClass;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Output\ClassList;

/**
 * Bridge from WordPress's render callback to Block::render(): parses the
 * raw attributes (the boundary), registers instance CSS, builds the context.
 */
final readonly class BlockRenderer
{
	public function __construct(
		private BreakpointConfig $breakpoints,
		private InstanceStyleCollector $collector,
		private DefaultsRepository $defaults,
	) {
	}

	/**
	 * @param array<mixed> $attributes Raw block attributes from WordPress.
	 */
	public function render( Block $block, array $attributes, string $content ): string
	{
		$definition = $block->definition();
		$values     = BlockValues::fromAttribute( $attributes[ BlockRegistry::ATTRIBUTE ] ?? null, $definition, $this->breakpoints->breakpoints() );
		$instance   = InstanceClass::of( $values );

		$classes = new ClassList( $definition->name->cssClass() );
		if ( $this->collector->add( $instance, $definition, $values ) ) {
			$classes = $classes->with( new ClassList( $instance->value ) );
		}
		$userClasses = $attributes['className'] ?? null;
		if ( is_string( $userClasses ) ) {
			$classes = $classes->with( ClassList::parse( $userClasses ) );
		}

		return $block->render( new RenderContext( $definition, $values, $this->defaults->for( $definition ), $classes, $content ) );
	}
}
