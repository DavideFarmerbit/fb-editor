<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Values\Side;

/**
 * Four-part CSS properties a BoxValue can drive, and the longhand each part
 * writes. For radius the box's sides stand for corners, clockwise from the
 * top left (top → top-left, right → top-right…).
 */
enum BoxProperty
{
	case Padding;
	case Margin;
	/**
	 * Padding as custom properties (--fb-padding-top…), for blocks whose
	 * base CSS composes the real padding from it (Container: content width).
	 */
	case PaddingVars;
	case BorderRadius;
	/** top / right / bottom / left of a positioned box. */
	case Inset;

	public function longhand( Side $side ): CssProperty
	{
		return match ( $this ) {
			self::Padding      => CssProperty::padding( $side ),
			self::Margin       => CssProperty::margin( $side ),
			self::PaddingVars  => CssProperty::paddingVar( $side ),
			self::BorderRadius => match ( $side ) {
				Side::Top    => CssProperty::BorderTopLeftRadius,
				Side::Right  => CssProperty::BorderTopRightRadius,
				Side::Bottom => CssProperty::BorderBottomRightRadius,
				Side::Left   => CssProperty::BorderBottomLeftRadius,
			},
			self::Inset        => match ( $side ) {
				Side::Top    => CssProperty::Top,
				Side::Right  => CssProperty::Right,
				Side::Bottom => CssProperty::Bottom,
				Side::Left   => CssProperty::Left,
			},
		};
	}
}
