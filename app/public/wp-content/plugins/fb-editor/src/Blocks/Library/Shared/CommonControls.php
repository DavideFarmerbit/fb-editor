<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Shared;

use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\AspectRatio\AspectRatioInput;
use Fb\Blocks\Definition\Input\Color\ColorInput;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Input\Number\NumberInput;
use Fb\Blocks\Definition\Input\Preset\PresetInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Spacing\SpacingInput;
use Fb\Blocks\Options\BorderStyle;
use Fb\Blocks\Options\FontWeight;
use Fb\Blocks\Options\TextAlign;
use Fb\Presets\PresetKind;
use Fb\Styles\CssProperty;
use Fb\Styles\Output\AxisOutput;
use Fb\Styles\Output\BoxOutput;
use Fb\Styles\Output\BoxProperty;
use Fb\Styles\Output\PropertyOutput;

/**
 * Controls many blocks share, so each is defined once. Every factory takes
 * the element it applies to (null: the block root).
 */
final class CommonControls
{
	public const string TEXT_ALIGN  = 'textAlign';
	public const string FONT_FAMILY = 'fontFamily';
	public const string FONT_SIZE   = 'fontSize';
	public const string FONT_WEIGHT = 'fontWeight';
	public const string TEXT_COLOR  = 'textColor';
	public const string BACKGROUND  = 'background';
	public const string MARGIN      = 'margin';
	public const string PADDING     = 'padding';
	public const string GAP         = 'gap';
	public const string LINE_HEIGHT    = 'lineHeight';
	public const string LETTER_SPACING = 'letterSpacing';
	public const string BORDER_WIDTH   = 'borderWidth';
	public const string BORDER_STYLE   = 'borderStyle';
	public const string BORDER_COLOR   = 'borderColor';
	public const string BORDER_RADIUS  = 'borderRadius';
	public const string SHADOW         = 'shadow';
	public const string GRADIENT       = 'gradient';
	public const string ASPECT_RATIO   = 'aspectRatio';

	/** Line heights beyond this are typos, not designs. */
	private const float MAX_LINE_HEIGHT = 10.0;

	public static function textAlign( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::TEXT_ALIGN ), __( 'Text align', 'fb-editor' ), new SelectInput( TextAlign::class ), new PropertyOutput( CssProperty::TextAlign ), $element );
	}

	public static function fontFamily( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::FONT_FAMILY ), __( 'Font family', 'fb-editor' ), new PresetInput( PresetKind::FontFamily ), new PropertyOutput( CssProperty::FontFamily ), $element );
	}

	public static function fontSize( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::FONT_SIZE ), __( 'Font size', 'fb-editor' ), new LengthInput( scale: PresetKind::FontSize ), new PropertyOutput( CssProperty::FontSize ), $element );
	}

	public static function fontWeight( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::FONT_WEIGHT ), __( 'Font weight', 'fb-editor' ), new SelectInput( FontWeight::class ), new PropertyOutput( CssProperty::FontWeight ), $element );
	}

	public static function textColor( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::TEXT_COLOR ), __( 'Text color', 'fb-editor' ), new ColorInput(), new PropertyOutput( CssProperty::Color ), $element );
	}

	public static function background( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::BACKGROUND ), __( 'Background', 'fb-editor' ), new ColorInput(), new PropertyOutput( CssProperty::BackgroundColor ), $element );
	}

	public static function margin( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::MARGIN ), __( 'Margin', 'fb-editor' ), SpacingInput::margin(), new BoxOutput( BoxProperty::Margin ), $element );
	}

	public static function padding( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::PADDING ), __( 'Padding', 'fb-editor' ), SpacingInput::padding(), new BoxOutput( BoxProperty::Padding ), $element );
	}

	public static function gap( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::GAP ), __( 'Gap', 'fb-editor' ), SpacingInput::gap(), AxisOutput::gap(), $element );
	}

	public static function lineHeight( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::LINE_HEIGHT ), __( 'Line height', 'fb-editor' ), new NumberInput( min: 0.0, max: self::MAX_LINE_HEIGHT, step: 0.05 ), new PropertyOutput( CssProperty::LineHeight ), $element );
	}

	public static function letterSpacing( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::LETTER_SPACING ), __( 'Letter spacing', 'fb-editor' ), new LengthInput( allowNegative: true ), new PropertyOutput( CssProperty::LetterSpacing ), $element );
	}

	public static function borderWidth( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::BORDER_WIDTH ), __( 'Border width', 'fb-editor' ), SpacingInput::borderWidth(), new BoxOutput( BoxProperty::BorderWidth ), $element );
	}

	public static function borderStyle( ?ElementId $element = null ): Control
	{
		return new Control(
			new ControlId( self::BORDER_STYLE ),
			__( 'Border style', 'fb-editor' ),
			new SelectInput( BorderStyle::class ),
			new PropertyOutput( CssProperty::BorderTopStyle, CssProperty::BorderRightStyle, CssProperty::BorderBottomStyle, CssProperty::BorderLeftStyle ),
			$element
		);
	}

	public static function borderColor( ?ElementId $element = null ): Control
	{
		return new Control(
			new ControlId( self::BORDER_COLOR ),
			__( 'Border color', 'fb-editor' ),
			new ColorInput(),
			new PropertyOutput( CssProperty::BorderTopColor, CssProperty::BorderRightColor, CssProperty::BorderBottomColor, CssProperty::BorderLeftColor ),
			$element
		);
	}

	public static function borderRadius( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::BORDER_RADIUS ), __( 'Radius', 'fb-editor' ), SpacingInput::borderRadius(), new BoxOutput( BoxProperty::BorderRadius ), $element );
	}

	public static function shadow( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::SHADOW ), __( 'Shadow', 'fb-editor' ), new PresetInput( PresetKind::Shadow ), new PropertyOutput( CssProperty::BoxShadow ), $element );
	}

	public static function gradient( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::GRADIENT ), __( 'Gradient', 'fb-editor' ), new PresetInput( PresetKind::Gradient ), new PropertyOutput( CssProperty::BackgroundImage ), $element );
	}

	public static function aspectRatio( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::ASPECT_RATIO ), __( 'Aspect ratio', 'fb-editor' ), new AspectRatioInput(), new PropertyOutput( CssProperty::AspectRatio ), $element );
	}

	/**
	 * Border width, style, colour and radius, for a block's panel.
	 *
	 * @return list<Control>
	 */
	public static function border( ?ElementId $element = null ): array
	{
		return [ self::borderWidth( $element ), self::borderStyle( $element ), self::borderColor( $element ), self::borderRadius( $element ) ];
	}

	public static function id( string $id ): ControlId
	{
		return new ControlId( $id );
	}
}
