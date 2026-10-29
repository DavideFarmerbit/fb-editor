<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Actions\Anchor\BlockAnchor;
use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Hiding\Hiding;
use Fb\Blocks\Values\BlockAttribute;
use Fb\Blocks\Values\InstanceClass;
use Fb\Blocks\Variants\Variant;
use Fb\Blocks\Variants\VariantsRepository;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Output\ClassList;
use Fb\Values\ResponsiveValue;

/**
 * Raw `fb` attribute in → the root's fb classes out (hide classes
 * included), with the instance and variant CSS registered for the page.
 * Shared by fb blocks (BlockRenderer)
 * and managed core blocks (BlockMarkupCleaner), so both render alike.
 */
final readonly class BlockStyler
{
	public function __construct(
		private BreakpointConfig $breakpoints,
		private InstanceStyleCollector $collector,
		private VariantsRepository $variants,
		private DefaultsRepository $defaults,
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

		$classes = $classes->with( Hiding::classes( $this->hideOn( $definition, $parsed, $variant ), $this->breakpoints->breakpoints() ) );

		// What other blocks' actions find it by.
		$anchor = BlockAnchor::ofAttribute( $attribute );
		if ( null !== $anchor ) {
			$classes = $classes->with( new ClassList( $anchor->className() ) );
		}

		return new StyledBlock( $parsed, $variant, $classes );
	}

	/** "Hide on": the block's own, else its variant's, else the type default's. */
	private function hideOn( BlockDefinition $definition, BlockAttribute $parsed, ?Variant $variant ): ?ResponsiveValue
	{
		$id   = new ControlId( Hiding::CONTROL );
		$base = $parsed->values->base->baseBreakpoint();

		return $parsed->values->base->get( $id )->at( $base )
			?? $variant?->values->base->get( $id )->at( $base )
			?? $this->defaults->for( $definition )->base->get( $id )->at( $base );
	}
}
