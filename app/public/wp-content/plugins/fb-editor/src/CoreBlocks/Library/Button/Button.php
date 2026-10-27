<?php

declare(strict_types=1);

namespace Fb\CoreBlocks\Library\Button;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Input\Svg\SvgInput;
use Fb\Blocks\Definition\Input\Toggle\ToggleInput;
use Fb\Blocks\Definition\Element;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Definition\Scope;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\States\BlockState;
use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\CoreBlocks\CoreBlock;
use Fb\CoreBlocks\ManagedCoreBlock;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Styles\Output\MaskIconOutput;
use Fb\Styles\Output\PropertyOutput;
use Fb\Styles\Output\ToggleOutput;
use Fb\Styles\PseudoElement;
use Fb\Values\KeywordValue;

/**
 * core/button: styling goes on the link (<a>), which core renders as a
 * direct child of the block wrapper.
 */
final class Button extends CoreBlock
{
	public const string LINK       = 'link';
	public const string ICON       = 'icon';
	public const string ICON_AFTER = 'iconAfter';
	public const string ICON_SIZE  = 'iconSize';
	public const string ICON_GAP   = 'iconGap';

	public function managed(): ManagedCoreBlock
	{
		return ManagedCoreBlock::Button;
	}

	protected function define(): BlockDefinition
	{
		$link = new ElementId( self::LINK );
		$id   = static fn ( string $value ): ControlId => new ControlId( $value );

		return new BlockDefinition(
			BlockName::core( 'button' ),
			__( 'Button', 'fb-editor' ),
			new ControlSet(
				C::background( $link ),
				C::gradient( $link ),
				C::textColor( $link ),
				C::fontFamily( $link ),
				C::fontSize( $link ),
				C::fontWeight( $link ),
				C::lineHeight( $link ),
				C::letterSpacing( $link ),
				C::padding( $link ),
				C::shadow( $link ),
				new Control( $id( self::ICON ), __( 'Icon (SVG code)', 'fb-editor' ), new SvgInput(), new MaskIconOutput(), $link, Scope::Static ),
				new Control(
					$id( self::ICON_AFTER ),
					__( 'Icon after the text', 'fb-editor' ),
					new ToggleInput(),
					new ToggleOutput( CssProperty::IconOrderVar, new KeywordValue( '1' ), new KeywordValue( '0' ) ),
					$link
				),
				new Control( $id( self::ICON_SIZE ), __( 'Icon size', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::IconSizeVar ), $link ),
				new Control( $id( self::ICON_GAP ), __( 'Space to the text', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::ColumnGap ), $link ),
				...C::textOptions( $link ),
				...C::border( $link ),
			),
			new Layout(
				new Panel( __( 'Colors', 'fb-editor' ), C::id( C::BACKGROUND ), C::id( C::GRADIENT ), C::id( C::TEXT_COLOR ) ),
				new Panel( __( 'Typography', 'fb-editor' ), C::id( C::FONT_FAMILY ), C::id( C::FONT_SIZE ), C::id( C::FONT_WEIGHT ), C::id( C::LINE_HEIGHT ), C::id( C::LETTER_SPACING ), ...C::textOptionIds() ),
				new Panel( __( 'Spacing', 'fb-editor' ), C::id( C::PADDING ) ),
				new Panel( __( 'Icon', 'fb-editor' ), $id( self::ICON ), $id( self::ICON_AFTER ), $id( self::ICON_SIZE ), $id( self::ICON_GAP ) ),
				new Panel(
					__( 'Border & shadow', 'fb-editor' ),
					C::id( C::BORDER ),
					C::id( C::BORDER_RADIUS ),
					C::id( C::SHADOW )
				),
			),
			// In the editor the link keeps core's class.
			[ new Element( $link, 'wp-block-button__link' ) ],
			BlockState::cases(),
		);
	}

	/**
	 * A link that looks like a button: a box, no underline, icon and text
	 * centered side by side. The icon is a ::before drawn in the text color
	 * through the SVG as a mask, only once an icon is set (MaskIconOutput
	 * sets --fb-icon-content); --fb-icon-order puts it after the text.
	 */
	public function baseStyles(): array
	{
		$link = new ElementId( self::LINK );
		$var  = static fn ( CssProperty $property, string $fallback ): string => 'var(' . $property->value . ',' . $fallback . ')';

		return [
			new BaseStyle(
				DeclarationSet::empty()
					->with( CssProperty::Display, 'inline-flex' )
					->with( CssProperty::AlignItems, 'center' )
					->with( CssProperty::JustifyContent, 'center' )
					->with( CssProperty::ColumnGap, '0.5em' )
					->with( CssProperty::TextDecoration, 'none' ),
				$link
			),
			new BaseStyle(
				DeclarationSet::empty()
					->with( CssProperty::Content, $var( CssProperty::IconContentVar, 'none' ) )
					->with( CssProperty::Flex, 'none' )
					->with( CssProperty::Order, $var( CssProperty::IconOrderVar, '0' ) )
					->with( CssProperty::Width, $var( CssProperty::IconSizeVar, '1em' ) )
					->with( CssProperty::Height, $var( CssProperty::IconSizeVar, '1em' ) )
					->with( CssProperty::BackgroundColor, 'currentColor' )
					->with( CssProperty::MaskImage, 'var(' . CssProperty::IconVar->value . ')' )
					->with( CssProperty::MaskPosition, 'center' )
					->with( CssProperty::MaskSize, 'contain' )
					->with( CssProperty::MaskRepeat, 'no-repeat' ),
				$link,
				pseudoElement: PseudoElement::Before
			),
		];
	}
}
