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
use Fb\Blocks\Definition\Input\SelectInput;
use Fb\Blocks\Definition\Input\SpacingInput;
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

/**
 * Layout container:
 *
 *   <section class="fb-section [instance] [user]">
 *     <div class="fb-section__inner">…inner blocks…</div>
 *   </section>
 *
 * Padding/margin on the root; display and gap on the inner element, so the
 * root can hold a background while the inner lays out children.
 */
final class Section extends Block
{
	public const string NAME = 'section';

	private ControlId $tag;
	private ControlId $display;
	private ControlId $gap;
	private ControlId $padding;
	private ControlId $margin;
	private ElementId $inner;

	public function __construct()
	{
		$this->tag     = new ControlId( 'tag' );
		$this->display = new ControlId( 'display' );
		$this->gap     = new ControlId( 'gap' );
		$this->padding = new ControlId( 'padding' );
		$this->margin  = new ControlId( 'margin' );
		$this->inner   = new ElementId( 'inner' );
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
				new Control( $this->tag, __( 'HTML tag', 'fb-editor' ), new SelectInput( ContainerTag::class ), scope: Scope::Static ),
				new Control( $this->display, __( 'Display', 'fb-editor' ), new SelectInput( Display::class ), new PropertyOutput( CssProperty::Display ), $this->inner ),
				new Control(
					$this->gap,
					__( 'Gap', 'fb-editor' ),
					SpacingInput::gap(),
					AxisOutput::gap(),
					$this->inner,
					visibleWhen: new In( $this->display, Display::Flex, Display::Grid )
				),
				new Control( $this->padding, __( 'Padding', 'fb-editor' ), SpacingInput::padding(), new BoxOutput( BoxProperty::Padding ) ),
				new Control( $this->margin, __( 'Margin', 'fb-editor' ), SpacingInput::margin(), new BoxOutput( BoxProperty::Margin ) ),
			),
			new Layout(
				new Panel( __( 'Layout', 'fb-editor' ), $this->tag, $this->display, $this->gap ),
				new Panel( __( 'Spacing', 'fb-editor' ), $this->padding, $this->margin ),
			),
			[ $this->inner ],
		);
	}

	public function render( RenderContext $context ): string
	{
		$tag = ( $context->option( $this->tag, ContainerTag::class ) ?? ContainerTag::Section )->value;

		return sprintf(
			'<%1$s class="%2$s"><div class="%3$s">%4$s</div></%1$s>',
			$tag,
			$context->rootClassAttribute(),
			$context->elementClassAttribute( $this->inner ),
			$context->content
		);
	}
}
