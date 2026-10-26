<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Values\BlockAttribute;
use Fb\Blocks\Values\InstanceClass;
use Fb\Blocks\Variants\VariantsRepository;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Output\ClassList;

/**
 * Raw `fb` attribute in → the root's fb classes out, with the instance and
 * variant CSS registered for the page. Shared by fb blocks (BlockRenderer)
 * and managed core blocks (BlockMarkupCleaner), so both render alike.
 */
final readonly class BlockStyler
{
	public function __construct(
		private BreakpointConfig $breakpoints,
		private InstanceStyleCollector $collector,
		private VariantsRepository $variants,
	) {
	}

	public function style( BlockDefinition $definition, mixed $attribute ): StyledBlock
	{
		$parsed  = BlockAttribute::fromJson( $attribute, $definition, $this->breakpoints->breakpoints() );
		$variant = null === $parsed->variant ? null : $this->variants->find( $definition, $parsed->variant );

		$classes = new ClassList( $definition->name->cssClass() );
		if ( null !== $variant ) {
			$classes = $classes->with( new ClassList( $definition->name->variantClass( $variant->slug ) ) );
		}

		$instance = InstanceClass::of( $parsed );
		if ( $this->collector->add( $instance, $definition, $parsed->values, $variant ) ) {
			$classes = $classes->with( new ClassList( $instance->value ) );
		}

		return new StyledBlock( $parsed, $variant, $classes );
	}
}
