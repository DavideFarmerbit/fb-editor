<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\Select\LabeledOption;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Values\StatefulValues;
use Fb\Output\ClassList;
use Fb\Values\KeywordValue;

/**
 * Everything a block's render() needs, typed: its classes, its values and
 * its inner content. Blocks never touch raw `$attributes`.
 */
final readonly class RenderContext
{
	public function __construct(
		public BlockDefinition $definition,
		public StatefulValues $values,
		/** The variant the block uses, if any: what unset values fall back to first. */
		public ?StatefulValues $variant,
		/** The block type's defaults: what unset values fall back to last. */
		public StatefulValues $defaults,
		/** Block class, variant class, instance class (if it has CSS), user classes. */
		public ClassList $rootClasses,
		/** Rendered inner blocks. Already HTML: output as is. */
		public string $content,
	) {
	}

	public function rootClassAttribute(): string
	{
		return esc_attr( (string) $this->rootClasses );
	}

	public function elementClassAttribute( ElementId $element ): string
	{
		return esc_attr( $this->definition->name->elementClass( $element ) );
	}

	/**
	 * The base value of a select control as its enum case (e.g. the tag):
	 * the block's own, else its variant's, else the type default, else the
	 * input's fallback.
	 * Selects read by render() must declare a fallback.
	 *
	 * @template T of LabeledOption
	 * @param class-string<T> $options Must be the control's option enum.
	 * @return T
	 */
	public function option( ControlId $id, string $options ): LabeledOption
	{
		$input = $this->definition->controls->get( $id )?->input;

		if ( ! $input instanceof SelectInput || $input->options !== $options ) {
			throw new \LogicException( "{$this->definition->name}: \"{$id}\" is not a select over {$options}." );
		}

		$base   = $this->values->base->baseBreakpoint();
		$value  = $this->values->base->get( $id )->at( $base )
			?? $this->variant?->base->get( $id )->at( $base )
			?? $this->defaults->base->get( $id )->at( $base );
		$chosen = $value instanceof KeywordValue ? $options::tryFrom( $value->keyword ) : null;

		$fallback = $input->fallback instanceof $options ? $input->fallback : null;

		return $chosen ?? $fallback ?? throw new \LogicException( "{$this->definition->name}: \"{$id}\" is read by render() but has no fallback." );
	}
}
