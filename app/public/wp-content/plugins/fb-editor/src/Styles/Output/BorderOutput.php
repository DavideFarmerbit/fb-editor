<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Values\BorderBox;
use Fb\Values\ResponsiveValue;
use Fb\Values\Side;

/**
 * BorderBox → border-<side>-width / -style / -color, only the parts set.
 */
final readonly class BorderOutput implements StyleOutput
{
	public function properties(): array
	{
		$properties = [];
		foreach ( Side::cases() as $side ) {
			array_push( $properties, ...self::longhands( $side ) );
		}

		return $properties;
	}

	public function declarations( ?ResponsiveValue $value ): DeclarationSet
	{
		$declarations = DeclarationSet::empty();
		if ( ! $value instanceof BorderBox ) {
			return $declarations;
		}

		foreach ( Side::cases() as $side ) {
			$border = $value->get( $side );
			if ( null === $border ) {
				continue;
			}
			[ $width, $style, $color ] = self::longhands( $side );
			if ( null !== $border->width ) {
				$declarations = $declarations->with( $width, $border->width->toCss() );
			}
			if ( null !== $border->style ) {
				$declarations = $declarations->with( $style, $border->style->value );
			}
			if ( null !== $border->color ) {
				$declarations = $declarations->with( $color, $border->color->toCss() );
			}
		}

		return $declarations;
	}

	/**
	 * Width, style and color longhands of a side, in that order.
	 *
	 * @return array{0: CssProperty, 1: CssProperty, 2: CssProperty}
	 */
	private static function longhands( Side $side ): array
	{
		return match ( $side ) {
			Side::Top    => [ CssProperty::BorderTopWidth, CssProperty::BorderTopStyle, CssProperty::BorderTopColor ],
			Side::Right  => [ CssProperty::BorderRightWidth, CssProperty::BorderRightStyle, CssProperty::BorderRightColor ],
			Side::Bottom => [ CssProperty::BorderBottomWidth, CssProperty::BorderBottomStyle, CssProperty::BorderBottomColor ],
			Side::Left   => [ CssProperty::BorderLeftWidth, CssProperty::BorderLeftStyle, CssProperty::BorderLeftColor ],
		};
	}

	/**
	 * @return array{kind: 'border'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'border' ];
	}
}
