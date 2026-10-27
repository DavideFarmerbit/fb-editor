<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Container;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Condition\In;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Input\Number\NumberInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Definition\Placement;
use Fb\Blocks\Options\AlignSelf;
use Fb\Blocks\Options\Display;
use Fb\Styles\CssProperty;
use Fb\Styles\Output\PropertyOutput;
use Fb\Styles\Output\SpanOutput;

/**
 * Settings every block gets while it's a direct child of a Container: how
 * it behaves as a flex or grid item. Each applies only where the
 * Container's display (at the inspected breakpoint) makes it meaningful.
 */
final class ContainerItem
{
	public const string ORDER       = 'itemOrder';
	public const string GROW        = 'itemGrow';
	public const string SHRINK      = 'itemShrink';
	public const string BASIS       = 'itemBasis';
	public const string ALIGN_SELF  = 'itemAlignSelf';
	public const string COLUMN_SPAN = 'itemColumnSpan';
	public const string ROW_SPAN    = 'itemRowSpan';

	/** Upper bound for flex-grow / flex-shrink factors. */
	private const float MAX_FACTOR = 100.0;

	public static function extend( BlockDefinition $definition ): BlockDefinition
	{
		return $definition->withControls( self::controls(), self::panel() );
	}

	private static function controls(): ControlSet
	{
		$container = new BlockName( Container::NAME );
		$display   = new ControlId( Container::DISPLAY );
		$flex      = new Placement( $container, new In( $display, Display::Flex ) );
		$grid      = new Placement( $container, new In( $display, Display::Grid ) );
		$either    = new Placement( $container, new In( $display, Display::Flex, Display::Grid ) );
		$factor    = new NumberInput( min: 0.0, max: self::MAX_FACTOR, step: 1.0 );
		$span      = new NumberInput( min: 1.0, max: 12.0, step: 1.0, integer: true );

		return new ControlSet(
			new Control( new ControlId( self::GROW ), __( 'Grow', 'fb-editor' ), $factor, new PropertyOutput( CssProperty::FlexGrow ), placement: $flex ),
			new Control( new ControlId( self::SHRINK ), __( 'Shrink', 'fb-editor' ), $factor, new PropertyOutput( CssProperty::FlexShrink ), placement: $flex ),
			new Control( new ControlId( self::BASIS ), __( 'Basis', 'fb-editor' ), new LengthInput( allowAuto: true ), new PropertyOutput( CssProperty::FlexBasis ), placement: $flex ),
			new Control( new ControlId( self::COLUMN_SPAN ), __( 'Column span', 'fb-editor' ), $span, new SpanOutput( CssProperty::GridColumn ), placement: $grid ),
			new Control( new ControlId( self::ROW_SPAN ), __( 'Row span', 'fb-editor' ), $span, new SpanOutput( CssProperty::GridRow ), placement: $grid ),
			new Control( new ControlId( self::ALIGN_SELF ), __( 'Align self', 'fb-editor' ), new SelectInput( AlignSelf::class ), new PropertyOutput( CssProperty::AlignSelf ), placement: $either ),
			new Control(
				new ControlId( self::ORDER ),
				__( 'Order', 'fb-editor' ),
				new NumberInput( min: -99.0, max: 99.0, step: 1.0, integer: true ),
				new PropertyOutput( CssProperty::Order ),
				placement: $either
			),
		);
	}

	private static function panel(): Panel
	{
		return new Panel(
			__( 'Container item', 'fb-editor' ),
			new ControlId( self::GROW ),
			new ControlId( self::SHRINK ),
			new ControlId( self::BASIS ),
			new ControlId( self::COLUMN_SPAN ),
			new ControlId( self::ROW_SPAN ),
			new ControlId( self::ALIGN_SELF ),
			new ControlId( self::ORDER ),
		);
	}
}
