<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\LabeledOption;
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
	 * The base value of a select control as its enum case, e.g. the tag.
	 *
	 * @template T of LabeledOption
	 * @param class-string<T> $options
	 * @return T|null
	 */
	public function option( ControlId $id, string $options ): ?LabeledOption
	{
		$value = $this->values->get( $id )->at( $this->values->baseBreakpoint() );

		return $value instanceof KeywordValue ? $options::tryFrom( $value->keyword ) : null;
	}
}
