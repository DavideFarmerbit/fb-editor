<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\LabeledOption;
use Fb\Blocks\Definition\Input\SelectInput;
use Fb\Blocks\Values\BlockValues;
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
		public BlockValues $values,
		/** Block class, instance class (if it has CSS), user classes. */
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
	 * The base value of a select control as its enum case (e.g. the tag),
	 * or the input's fallback while nothing is chosen. Selects read by
	 * render() must declare a fallback.
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

		$value  = $this->values->get( $id )->at( $this->values->baseBreakpoint() );
		$chosen = $value instanceof KeywordValue ? $options::tryFrom( $value->keyword ) : null;

		$fallback = $input->fallback instanceof $options ? $input->fallback : null;

		return $chosen ?? $fallback ?? throw new \LogicException( "{$this->definition->name}: \"{$id}\" is read by render() but has no fallback." );
	}
}
