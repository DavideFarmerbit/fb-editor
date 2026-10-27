<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Values\NumberValue;
use Fb\Values\ResponsiveValue;

/**
 * A whole number of grid tracks to span: `grid-column: span 2`.
 */
final readonly class SpanOutput implements StyleOutput
{
	public function __construct(
		public CssProperty $property,
	) {
	}

	public function properties(): array
	{
		return [ $this->property ];
	}

	public function declarations( ?ResponsiveValue $value ): DeclarationSet
	{
		if ( ! $value instanceof NumberValue || ! is_int( $value->value ) || $value->value < 1 ) {
			return DeclarationSet::empty();
		}

		return DeclarationSet::empty()->with( $this->property, "span {$value->value}" );
	}

	/**
	 * @return array{kind: 'span', property: string}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'span', 'property' => $this->property->value ];
	}
}
