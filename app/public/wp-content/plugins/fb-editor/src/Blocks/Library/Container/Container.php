<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Container;

use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\Blocks\Block;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Condition\In;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Color\ColorInput;
use Fb\Blocks\Definition\Input\GridColumns\GridColumnsInput;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Spacing\SpacingInput;
use Fb\Blocks\Definition\Input\Toggle\ToggleInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Definition\Scope;
use Fb\Blocks\Options\AlignContent;
use Fb\Blocks\Options\AlignItems;
use Fb\Blocks\Options\ContainerTag;
use Fb\Blocks\Options\Display;
use Fb\Blocks\Options\FlexDirection;
use Fb\Blocks\Options\Justify;
use Fb\Blocks\Options\JustifyItems;
use Fb\Blocks\Rendering\RenderContext;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Styles\Output\AxisOutput;
use Fb\Styles\Output\BoxOutput;
use Fb\Styles\Output\BoxProperty;
use Fb\Styles\Output\PropertyOutput;
use Fb\Styles\Output\ToggleOutput;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\KeywordValue;

/**
 * General-purpose layout block: a block, flex or grid container whose
 * children are its direct children (they get "Container item" settings):
 *
 *   <div class="fb-container [variant] [instance] [user]">…inner blocks…</div>
 *
 * Content width ("boxed") needs no inner wrapper: the side padding grows so
 * the content area never exceeds it, while backgrounds still span the
 * whole box. Padding and content width are custom properties the base CSS
 * composes (see baseStyles()).
 */
final class Container extends Block
{
	/*
	 * Names the editor markup (edit.tsx) shares with render(): exported to
	 * Container.generated.ts, never repeated as literals.
	 */
	#[ExportToTypeScript]
	public const string NAME = 'container';
	#[ExportToTypeScript]
	public const string TAG = 'tag';

	#[ExportToTypeScript]
	public const string DISPLAY        = 'display';
	public const string DIRECTION      = 'direction';
	public const string WRAP           = 'wrap';
	public const string COLUMNS        = 'columns';
	public const string JUSTIFY        = 'justify';
	public const string ALIGN_ITEMS    = 'alignItems';
	public const string JUSTIFY_ITEMS  = 'justifyItems';
	public const string ALIGN_CONTENT  = 'alignContent';
	public const string GAP            = 'gap';
	public const string CONTENT_WIDTH  = 'contentWidth';
	public const string MAX_WIDTH      = 'maxWidth';
	public const string MIN_HEIGHT     = 'minHeight';
	public const string PADDING        = 'padding';
	public const string MARGIN         = 'margin';
	public const string BACKGROUND     = 'background';
	public const string TEXT_COLOR     = 'textColor';

	public function hasInnerBlocks(): bool
	{
		return true;
	}

	protected function define(): BlockDefinition
	{
		$id      = static fn ( string $value ): ControlId => new ControlId( $value );
		$display = $id( self::DISPLAY );
		$flex    = new In( $display, Display::Flex );
		$grid    = new In( $display, Display::Grid );
		$layout  = new In( $display, Display::Flex, Display::Grid );

		return new BlockDefinition(
			new BlockName( self::NAME ),
			__( 'Container', 'fb-editor' ),
			new ControlSet(
				new Control( $id( self::TAG ), __( 'HTML tag', 'fb-editor' ), new SelectInput( ContainerTag::class, ContainerTag::Div ), scope: Scope::Static ),
				new Control( $display, __( 'Display', 'fb-editor' ), new SelectInput( Display::class ), new PropertyOutput( CssProperty::Display ) ),
				new Control( $id( self::DIRECTION ), __( 'Direction', 'fb-editor' ), new SelectInput( FlexDirection::class ), new PropertyOutput( CssProperty::FlexDirection ), visibleWhen: $flex ),
				new Control(
					$id( self::WRAP ),
					__( 'Wrap', 'fb-editor' ),
					new ToggleInput(),
					new ToggleOutput( CssProperty::FlexWrap, new KeywordValue( 'wrap' ), new KeywordValue( 'nowrap' ) ),
					visibleWhen: $flex
				),
				new Control( $id( self::COLUMNS ), __( 'Columns', 'fb-editor' ), new GridColumnsInput(), new PropertyOutput( CssProperty::GridTemplateColumns ), visibleWhen: $grid ),
				new Control( $id( self::JUSTIFY ), __( 'Justify content', 'fb-editor' ), new SelectInput( Justify::class ), new PropertyOutput( CssProperty::JustifyContent ), visibleWhen: $layout ),
				new Control( $id( self::ALIGN_ITEMS ), __( 'Align items', 'fb-editor' ), new SelectInput( AlignItems::class ), new PropertyOutput( CssProperty::AlignItems ), visibleWhen: $layout ),
				new Control( $id( self::JUSTIFY_ITEMS ), __( 'Justify items', 'fb-editor' ), new SelectInput( JustifyItems::class ), new PropertyOutput( CssProperty::JustifyItems ), visibleWhen: $grid ),
				new Control( $id( self::ALIGN_CONTENT ), __( 'Align content', 'fb-editor' ), new SelectInput( AlignContent::class ), new PropertyOutput( CssProperty::AlignContent ), visibleWhen: $layout ),
				new Control( $id( self::GAP ), __( 'Gap', 'fb-editor' ), SpacingInput::gap(), AxisOutput::gap(), visibleWhen: $layout ),
				new Control( $id( self::CONTENT_WIDTH ), __( 'Content width', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::ContentWidthVar ) ),
				new Control( $id( self::MAX_WIDTH ), __( 'Max width', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::MaxWidth ) ),
				new Control( $id( self::MIN_HEIGHT ), __( 'Min height', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::MinHeight ) ),
				new Control( $id( self::PADDING ), __( 'Padding', 'fb-editor' ), SpacingInput::padding(), new BoxOutput( BoxProperty::PaddingVars ) ),
				new Control( $id( self::MARGIN ), __( 'Margin', 'fb-editor' ), SpacingInput::margin(), new BoxOutput( BoxProperty::Margin ) ),
				new Control( $id( self::BACKGROUND ), __( 'Background', 'fb-editor' ), new ColorInput(), new PropertyOutput( CssProperty::BackgroundColor ) ),
				new Control( $id( self::TEXT_COLOR ), __( 'Text', 'fb-editor' ), new ColorInput(), new PropertyOutput( CssProperty::Color ) ),
				C::gradient(),
				C::aspectRatio(),
				C::shadow(),
				...C::border(),
			),
			new Layout(
				new Panel(
					__( 'Layout', 'fb-editor' ),
					$id( self::TAG ),
					$display,
					$id( self::DIRECTION ),
					$id( self::WRAP ),
					$id( self::COLUMNS ),
					$id( self::JUSTIFY ),
					$id( self::ALIGN_ITEMS ),
					$id( self::JUSTIFY_ITEMS ),
					$id( self::ALIGN_CONTENT ),
					$id( self::GAP ),
				),
				new Panel( __( 'Size', 'fb-editor' ), $id( self::CONTENT_WIDTH ), $id( self::MAX_WIDTH ), $id( self::MIN_HEIGHT ), C::id( C::ASPECT_RATIO ) ),
				new Panel( __( 'Spacing', 'fb-editor' ), $id( self::PADDING ), $id( self::MARGIN ) ),
				new Panel( __( 'Colors', 'fb-editor' ), $id( self::BACKGROUND ), C::id( C::GRADIENT ), $id( self::TEXT_COLOR ) ),
				new Panel(
					__( 'Border & shadow', 'fb-editor' ),
					C::id( C::BORDER_WIDTH ),
					C::id( C::BORDER_STYLE ),
					C::id( C::BORDER_COLOR ),
					C::id( C::BORDER_RADIUS ),
					C::id( C::SHADOW )
				),
			),
		);
	}

	/**
	 * The padding the user sets, with the sides grown to keep the content
	 * area within the content width (centered):
	 *
	 *   padding-left: max(<padding left>, (100% - <content width>) / 2)
	 *
	 * The custom properties are reset here, on every container, so a nested
	 * container never inherits its parent's.
	 */
	public function baseStyles(): array
	{
		$side = static fn ( CssProperty $var ): string => 'max(var(' . $var->value . '),calc((100% - var(' . CssProperty::ContentWidthVar->value . '))/2))';

		return [
			new BaseStyle(
				DeclarationSet::empty()
					->with( CssProperty::PaddingTopVar, '0px' )
					->with( CssProperty::PaddingRightVar, '0px' )
					->with( CssProperty::PaddingBottomVar, '0px' )
					->with( CssProperty::PaddingLeftVar, '0px' )
					->with( CssProperty::ContentWidthVar, '100%' )
					->with( CssProperty::PaddingTop, 'var(' . CssProperty::PaddingTopVar->value . ')' )
					->with( CssProperty::PaddingBottom, 'var(' . CssProperty::PaddingBottomVar->value . ')' )
					->with( CssProperty::PaddingLeft, $side( CssProperty::PaddingLeftVar ) )
					->with( CssProperty::PaddingRight, $side( CssProperty::PaddingRightVar ) )
			),
		];
	}

	public function render( RenderContext $context ): string
	{
		$tag = $context->option( new ControlId( self::TAG ), ContainerTag::class )->value;

		return sprintf( '<%1$s class="%2$s">%3$s</%1$s>', $tag, $context->rootClassAttribute(), $context->content );
	}
}
