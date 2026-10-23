<?php

declare(strict_types=1);

namespace Fb\Styles;

use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\Side;

/**
 * CSS properties fb may write. Longhands only, so one control never wipes
 * out another (or a type default) by accident.
 */
#[ExportToTypeScript]
enum CssProperty: string
{
	case PaddingTop    = 'padding-top';
	case PaddingRight  = 'padding-right';
	case PaddingBottom = 'padding-bottom';
	case PaddingLeft   = 'padding-left';
	case MarginTop     = 'margin-top';
	case MarginRight   = 'margin-right';
	case MarginBottom  = 'margin-bottom';
	case MarginLeft    = 'margin-left';
	case RowGap        = 'row-gap';
	case ColumnGap     = 'column-gap';
	case Display       = 'display';
	case Width         = 'width';
	case MaxWidth      = 'max-width';
	case MinHeight     = 'min-height';

	public static function padding( Side $side ): self
	{
		return match ( $side ) {
			Side::Top    => self::PaddingTop,
			Side::Right  => self::PaddingRight,
			Side::Bottom => self::PaddingBottom,
			Side::Left   => self::PaddingLeft,
		};
	}

	public static function margin( Side $side ): self
	{
		return match ( $side ) {
			Side::Top    => self::MarginTop,
			Side::Right  => self::MarginRight,
			Side::Bottom => self::MarginBottom,
			Side::Left   => self::MarginLeft,
		};
	}
}
