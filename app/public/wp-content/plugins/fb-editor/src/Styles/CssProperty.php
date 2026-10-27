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
	case FlexWrap      = 'flex-wrap';
	case Color         = 'color';
	case BackgroundColor = 'background-color';
	case FontSize      = 'font-size';
	case FontFamily    = 'font-family';
	case FontWeight    = 'font-weight';
	case TextAlign     = 'text-align';
	case JustifyContent = 'justify-content';
	case Height        = 'height';
	case VerticalAlign = 'vertical-align';
	case TextDecoration = 'text-decoration';
	case LineHeight    = 'line-height';
	case FlexDirection = 'flex-direction';
	case AlignItems    = 'align-items';
	case AlignContent  = 'align-content';
	case JustifyItems  = 'justify-items';
	case GridTemplateColumns = 'grid-template-columns';
	case FlexGrow      = 'flex-grow';
	case FlexShrink    = 'flex-shrink';
	case FlexBasis     = 'flex-basis';
	case AlignSelf     = 'align-self';
	case Order         = 'order';
	case GridColumn    = 'grid-column';
	case GridRow       = 'grid-row';
	case LetterSpacing = 'letter-spacing';
	case BorderTopWidth    = 'border-top-width';
	case BorderRightWidth  = 'border-right-width';
	case BorderBottomWidth = 'border-bottom-width';
	case BorderLeftWidth   = 'border-left-width';
	case BorderTopStyle    = 'border-top-style';
	case BorderRightStyle  = 'border-right-style';
	case BorderBottomStyle = 'border-bottom-style';
	case BorderLeftStyle   = 'border-left-style';
	case BorderTopColor    = 'border-top-color';
	case BorderRightColor  = 'border-right-color';
	case BorderBottomColor = 'border-bottom-color';
	case BorderLeftColor   = 'border-left-color';
	case BorderTopLeftRadius     = 'border-top-left-radius';
	case BorderTopRightRadius    = 'border-top-right-radius';
	case BorderBottomRightRadius = 'border-bottom-right-radius';
	case BorderBottomLeftRadius  = 'border-bottom-left-radius';
	case BoxShadow       = 'box-shadow';
	case BackgroundImage = 'background-image';
	case Opacity  = 'opacity';
	case ZIndex   = 'z-index';
	case Position = 'position';
	case Top      = 'top';
	case Right    = 'right';
	case Bottom   = 'bottom';
	case Left     = 'left';
	case AspectRatio = 'aspect-ratio';
	// Custom properties composed by base CSS (see Container).
	case PaddingTopVar    = '--fb-padding-top';
	case PaddingRightVar  = '--fb-padding-right';
	case PaddingBottomVar = '--fb-padding-bottom';
	case PaddingLeftVar   = '--fb-padding-left';
	case ContentWidthVar  = '--fb-content-width';

	public static function padding( Side $side ): self
	{
		return match ( $side ) {
			Side::Top    => self::PaddingTop,
			Side::Right  => self::PaddingRight,
			Side::Bottom => self::PaddingBottom,
			Side::Left   => self::PaddingLeft,
		};
	}

	public static function paddingVar( Side $side ): self
	{
		return match ( $side ) {
			Side::Top    => self::PaddingTopVar,
			Side::Right  => self::PaddingRightVar,
			Side::Bottom => self::PaddingBottomVar,
			Side::Left   => self::PaddingLeftVar,
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
