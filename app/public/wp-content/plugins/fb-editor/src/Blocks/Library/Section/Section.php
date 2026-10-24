<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Section;

use Fb\Blocks\Block;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Condition\In;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\Color\ColorInput;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Spacing\SpacingInput;
use Fb\Blocks\Definition\Input\Toggle\ToggleInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Definition\Scope;
use Fb\Blocks\Options\ContainerTag;
use Fb\Blocks\Options\Display;
use Fb\Blocks\Rendering\RenderContext;
use Fb\Styles\CssProperty;
use Fb\Styles\Output\AxisOutput;
use Fb\Styles\Output\BoxOutput;
use Fb\Styles\Output\BoxProperty;
use Fb\Styles\Output\PropertyOutput;
use Fb\Styles\Output\ToggleOutput;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\KeywordValue;

/**
 * Layout container:
 *
 *   <section class="fb-section [instance] [user]">
 *     <div class="fb-section__inner">…inner blocks…</div>
 *   </section>
 *
 * Box (padding, margin, min-height, colors) on the root; layout (display,
 * gap, wrap, max-width) on the inner element, so the root can span the page
 * with a background while the inner lays out and constrains children.
 */
final class Section extends Block
{
	/*
	 * Names the editor markup (edit.tsx) shares with render(): exported to
	 * Section.generated.ts, never repeated as literals.
	 */
	#[ExportToTypeScript]
	public const string NAME = 'section';
	#[ExportToTypeScript]
	public const string TAG = 'tag';
	#[ExportToTypeScript]
	public const string DISPLAY = 'display';
	#[ExportToTypeScript]
	public const string GAP = 'gap';
	#[ExportToTypeScript]
	public const string PADDING = 'padding';
	#[ExportToTypeScript]
	public const string MARGIN = 'margin';
	#[ExportToTypeScript]
	public const string WRAP = 'wrap';
	#[ExportToTypeScript]
	public const string MAX_WIDTH = 'maxWidth';
	#[ExportToTypeScript]
	public const string MIN_HEIGHT = 'minHeight';
	#[ExportToTypeScript]
	public const string BACKGROUND = 'background';
	#[ExportToTypeScript]
	public const string TEXT_COLOR = 'textColor';
	#[ExportToTypeScript]
	public const string INNER = 'inner';

	private ControlId $tag;
	private ControlId $display;
	private ControlId $gap;
	private ControlId $padding;
	private ControlId $margin;
	private ControlId $wrap;
	private ControlId $maxWidth;
	private ControlId $minHeight;
	private ControlId $background;
	private ControlId $textColor;
	private ElementId $inner;

	public function __construct()
	{
		$this->tag     = new ControlId( self::TAG );
		$this->display = new ControlId( self::DISPLAY );
		$this->gap     = new ControlId( self::GAP );
		$this->padding = new ControlId( self::PADDING );
		$this->margin     = new ControlId( self::MARGIN );
		$this->wrap       = new ControlId( self::WRAP );
		$this->maxWidth   = new ControlId( self::MAX_WIDTH );
		$this->minHeight  = new ControlId( self::MIN_HEIGHT );
		$this->background = new ControlId( self::BACKGROUND );
		$this->textColor  = new ControlId( self::TEXT_COLOR );
		$this->inner      = new ElementId( self::INNER );
	}

	public function hasInnerBlocks(): bool
	{
		return true;
	}

	protected function define(): BlockDefinition
	{
		return new BlockDefinition(
			new BlockName( self::NAME ),
			__( 'Section', 'fb-editor' ),
			new ControlSet(
				new Control( $this->tag, __( 'HTML tag', 'fb-editor' ), new SelectInput( ContainerTag::class, ContainerTag::Section ), scope: Scope::Static ),
				new Control( $this->display, __( 'Display', 'fb-editor' ), new SelectInput( Display::class ), new PropertyOutput( CssProperty::Display ), $this->inner ),
				new Control(
					$this->gap,
					__( 'Gap', 'fb-editor' ),
					SpacingInput::gap(),
					AxisOutput::gap(),
					$this->inner,
					visibleWhen: new In( $this->display, Display::Flex, Display::Grid )
				),
				new Control(
					$this->wrap,
					__( 'Wrap', 'fb-editor' ),
					new ToggleInput(),
					new ToggleOutput( CssProperty::FlexWrap, new KeywordValue( 'wrap' ), new KeywordValue( 'nowrap' ) ),
					$this->inner,
					visibleWhen: new In( $this->display, Display::Flex )
				),
				new Control( $this->maxWidth, __( 'Content max width', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::MaxWidth ), $this->inner ),
				new Control( $this->minHeight, __( 'Min height', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::MinHeight ) ),
				new Control( $this->padding, __( 'Padding', 'fb-editor' ), SpacingInput::padding(), new BoxOutput( BoxProperty::Padding ) ),
				new Control( $this->margin, __( 'Margin', 'fb-editor' ), SpacingInput::margin(), new BoxOutput( BoxProperty::Margin ) ),
				new Control( $this->background, __( 'Background', 'fb-editor' ), new ColorInput(), new PropertyOutput( CssProperty::BackgroundColor ) ),
				new Control( $this->textColor, __( 'Text', 'fb-editor' ), new ColorInput(), new PropertyOutput( CssProperty::Color ) ),
			),
			new Layout(
				new Panel( __( 'Layout', 'fb-editor' ), $this->tag, $this->display, $this->gap, $this->wrap ),
				new Panel( __( 'Size', 'fb-editor' ), $this->maxWidth, $this->minHeight ),
				new Panel( __( 'Spacing', 'fb-editor' ), $this->padding, $this->margin ),
				new Panel( __( 'Colors', 'fb-editor' ), $this->background, $this->textColor ),
			),
			[ $this->inner ],
		);
	}

	public function render( RenderContext $context ): string
	{
		$tag = $context->option( $this->tag, ContainerTag::class )->value;

		return sprintf(
			'<%1$s class="%2$s"><div class="%3$s">%4$s</div></%1$s>',
			$tag,
			$context->rootClassAttribute(),
			$context->elementClassAttribute( $this->inner ),
			$context->content
		);
	}
}
