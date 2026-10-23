<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Values\BoxValue;
use Fb\Values\ResponsiveValue;

/**
 * BoxValue read per axis: top → block-axis property, left → inline-axis
 * property (e.g. row-gap / column-gap). The editor only offers linked and
 * axis modes for these, so bottom/right always mirror top/left.
 */
final readonly class AxisOutput implements StyleOutput
{
	public function __construct(
		public CssProperty $block,
		public CssProperty $inline,
	) {
	}

	public static function gap(): self
	{
		return new self( CssProperty::RowGap, CssProperty::ColumnGap );
	}

	public function properties(): array
	{
		return [ $this->block, $this->inline ];
	}

	public function declarations( ?ResponsiveValue $value ): DeclarationSet
	{
		$declarations = DeclarationSet::empty();

		if ( ! $value instanceof BoxValue ) {
			return $declarations;
		}
		if ( null !== $value->top ) {
			$declarations = $declarations->with( $this->block, $value->top->toCss() );
		}
		if ( null !== $value->left ) {
			$declarations = $declarations->with( $this->inline, $value->left->toCss() );
		}

		return $declarations;
	}

	/**
	 * @return array{kind: 'axis', block: string, inline: string}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'axis', 'block' => $this->block->value, 'inline' => $this->inline->value ];
	}
}
