<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Values\BooleanValue;
use Fb\Values\KeywordValue;
use Fb\Values\ResponsiveValue;

/**
 * BooleanValue → one keyword per state, e.g. on: `flex-wrap: wrap`,
 * off: `flex-wrap: nowrap`. The toggle stores intent; this decides CSS.
 */
final readonly class ToggleOutput implements StyleOutput
{
	public function __construct(
		public CssProperty $property,
		public KeywordValue $on,
		public KeywordValue $off,
	) {
	}

	public function properties(): array
	{
		return [ $this->property ];
	}

	public function declarations( ?ResponsiveValue $value ): DeclarationSet
	{
		if ( ! $value instanceof BooleanValue ) {
			return DeclarationSet::empty();
		}

		return DeclarationSet::empty()->with( $this->property, ( $value->on ? $this->on : $this->off )->toCss() );
	}

	/**
	 * @return array{kind: 'toggle', property: string, on: KeywordValue, off: KeywordValue}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'toggle', 'property' => $this->property->value, 'on' => $this->on, 'off' => $this->off ];
	}
}
