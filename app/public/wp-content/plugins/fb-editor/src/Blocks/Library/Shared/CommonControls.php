<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Shared;

use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\Color\ColorInput;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Input\Preset\PresetInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Spacing\SpacingInput;
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

	public static function id( string $id ): ControlId
	{
		return new ControlId( $id );
	}
}
