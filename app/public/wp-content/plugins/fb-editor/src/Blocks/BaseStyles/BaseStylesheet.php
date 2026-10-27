<?php

declare(strict_types=1);

namespace Fb\Blocks\BaseStyles;

use Fb\Blocks\BlockType;
use Fb\Blocks\Definition\Element;
use Fb\Css\CssLayer;
use Fb\Styles\RuleSet;
use Fb\Styles\Selector;

/**
 * The `fb.base` layer: functional CSS of block types (fb blocks and managed
 * core blocks), below type defaults so every bit of it can be overridden.
 */
final readonly class BaseStylesheet
{
	/**
	 * @param list<BlockType> $blocks
	 * @param bool            $editor Use core's element classes (editor canvas).
	 */
	public function css( array $blocks, bool $editor ): string
	{
		$rules = new RuleSet();

		foreach ( $blocks as $block ) {
			$definition = $block->definition();
			foreach ( $block->baseStyles() as $style ) {
				$selector = Selector::forClass( $definition->name->cssClass() );

				if ( null !== $style->element ) {
					$element  = $definition->element( $style->element );
					$selector = $selector->child( $this->elementClass( $definition->name->elementClass( $style->element ), $element, $editor ) );
				}
				if ( null !== $style->descendantTag ) {
					$selector = $selector->descendantTag( $style->descendantTag );
				}
				if ( null !== $style->pseudoElement ) {
					$selector = $selector->withPseudoElement( $style->pseudoElement );
				}

				$rules->add( $selector, $style->declarations );
			}
		}

		return $rules->isEmpty() ? '' : CssLayer::Base->wrap( $rules->toCss() );
	}

	private function elementClass( string $frontendClass, ?Element $element, bool $editor ): string
	{
		return $editor && null !== $element && null !== $element->editorClass ? $element->editorClass : $frontendClass;
	}
}
