<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Shared;

use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\AspectRatio\AspectRatioInput;
use Fb\Blocks\Definition\Input\Border\BorderInput;
use Fb\Blocks\Definition\Input\Color\ColorInput;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Input\Number\NumberInput;
use Fb\Blocks\Definition\Input\Preset\PresetInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Spacing\SpacingInput;
use Fb\Blocks\Options\FontWeight;
use Fb\Blocks\Options\TextAlign;
use Fb\Blocks\Options\TextDecoration;
use Fb\Blocks\Options\TextTransform;
use Fb\Presets\PresetKind;
use Fb\Styles\CssProperty;
use Fb\Styles\Output\AxisOutput;
use Fb\Styles\Output\BorderOutput;
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
	public const string TEXT_DECORATION = 'textDecoration';
	public const string TEXT_TRANSFORM  = 'textTransform';
	public const string TEXT_INDENT     = 'textIndent';
	public const string BORDER         = 'border';
	public const string BORDER_RADIUS  = 'borderRadius';
	public const string SHADOW         = 'shadow';
	public const string GRADIENT       = 'gradient';
	public const string ASPECT_RATIO   = 'aspectRatio';
	public const string WIDTH          = 'width';
	public const string MIN_WIDTH      = 'minWidth';
	public const string MAX_WIDTH      = 'maxWidth';
	public const string HEIGHT         = 'height';
	public const string MIN_HEIGHT     = 'minHeight';
	public const string MAX_HEIGHT     = 'maxHeight';

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

	/** Underline… (the `-line` longhand: combines with a base `text-decoration: none`). */
	public static function textDecoration( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::TEXT_DECORATION ), __( 'Decoration', 'fb-editor' ), new SelectInput( TextDecoration::class ), new PropertyOutput( CssProperty::TextDecorationLine ), $element );
	}

	public static function textTransform( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::TEXT_TRANSFORM ), __( 'Letter case', 'fb-editor' ), new SelectInput( TextTransform::class ), new PropertyOutput( CssProperty::TextTransform ), $element );
	}

	/** First-line indent; negative for a hanging indent. */
	public static function textIndent( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::TEXT_INDENT ), __( 'Indent', 'fb-editor' ), new LengthInput( allowNegative: true ), new PropertyOutput( CssProperty::TextIndent ), $element );
	}

	/**
	 * The text options every block with text gets, after its own
	 * typography controls.
	 *
	 * @return list<Control>
	 */
	public static function textOptions( ?ElementId $element = null ): array
	{
		return [ self::textDecoration( $element ), self::textTransform( $element ), self::textIndent( $element ) ];
	}

	/**
	 * @return list<ControlId>
	 */
	public static function textOptionIds(): array
	{
		return [ self::id( self::TEXT_DECORATION ), self::id( self::TEXT_TRANSFORM ), self::id( self::TEXT_INDENT ) ];
	}

	public static function borderSides( ?ElementId $element = null ): Control
	{
		return new Control( new ControlId( self::BORDER ), __( 'Border', 'fb-editor' ), new BorderInput(), new BorderOutput(), $element );
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
	 * Width and height with their minimums and maximums, in that order.
	 * Width and height also accept `auto`.
	 *
	 * @return list<Control>
	 */
	public static function size( ?ElementId $element = null ): array
	{
		$length = static fn ( string $id, string $label, CssProperty $property, bool $auto = false ): Control
			=> new Control( new ControlId( $id ), $label, new LengthInput( allowAuto: $auto ), new PropertyOutput( $property ), $element );

		return [
			$length( self::WIDTH, __( 'Width', 'fb-editor' ), CssProperty::Width, true ),
			$length( self::MIN_WIDTH, __( 'Min width', 'fb-editor' ), CssProperty::MinWidth ),
			$length( self::MAX_WIDTH, __( 'Max width', 'fb-editor' ), CssProperty::MaxWidth ),
			$length( self::HEIGHT, __( 'Height', 'fb-editor' ), CssProperty::Height, true ),
			$length( self::MIN_HEIGHT, __( 'Min height', 'fb-editor' ), CssProperty::MinHeight ),
			$length( self::MAX_HEIGHT, __( 'Max height', 'fb-editor' ), CssProperty::MaxHeight ),
		];
	}

	/**
	 * Ids of size(), for a panel.
	 *
	 * @return list<ControlId>
	 */
	public static function sizeIds(): array
	{
		return array_map( self::id( ... ), [ self::WIDTH, self::MIN_WIDTH, self::MAX_WIDTH, self::HEIGHT, self::MIN_HEIGHT, self::MAX_HEIGHT ] );
	}

	/**
	 * Border (width, style, color per side) and radius, for a block's panel.
	 *
	 * @return list<Control>
	 */
	public static function border( ?ElementId $element = null ): array
	{
		return [ self::borderSides( $element ), self::borderRadius( $element ) ];
	}

	public static function id( string $id ): ControlId
	{
		return new ControlId( $id );
	}
}
