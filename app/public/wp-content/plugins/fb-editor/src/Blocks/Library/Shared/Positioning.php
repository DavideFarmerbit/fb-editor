<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Shared;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\Condition\In;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Number\NumberInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Spacing\SpacingInput;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Options\Position;
use Fb\Styles\CssProperty;
use Fb\Styles\Output\BoxOutput;
use Fb\Styles\Output\BoxProperty;
use Fb\Styles\Output\PropertyOutput;

/**
 * Settings every block gets, in a "Position" panel: position and offsets,
 * stacking order, opacity. Part of the block's look: they can vary per
 * state and live in type defaults and variants.
 */
final class Positioning
{
	public const string POSITION = 'position';
	public const string INSET    = 'inset';
	public const string Z_INDEX  = 'zIndex';
	public const string OPACITY  = 'opacity';

	/** Stacking orders beyond this are an arms race, not a design. */
	private const float MAX_Z_INDEX = 9999.0;

	public static function extend( BlockDefinition $definition ): BlockDefinition
	{
		$position = new ControlId( self::POSITION );

		return $definition->withControls(
			new ControlSet(
				new Control( $position, __( 'Position', 'fb-editor' ), new SelectInput( Position::class ), new PropertyOutput( CssProperty::Position ) ),
				new Control(
					new ControlId( self::INSET ),
					__( 'Offsets', 'fb-editor' ),
					SpacingInput::inset(),
					new BoxOutput( BoxProperty::Inset ),
					visibleWhen: new In( $position, Position::Relative, Position::Absolute, Position::Sticky, Position::Fixed )
				),
				new Control(
					new ControlId( self::Z_INDEX ),
					__( 'Stacking order', 'fb-editor' ),
					new NumberInput( min: -self::MAX_Z_INDEX, max: self::MAX_Z_INDEX, step: 1.0, integer: true ),
					new PropertyOutput( CssProperty::ZIndex )
				),
				new Control( new ControlId( self::OPACITY ), __( 'Opacity', 'fb-editor' ), new NumberInput( min: 0.0, max: 1.0, step: 0.05 ), new PropertyOutput( CssProperty::Opacity ) ),
			),
			new Panel( __( 'Position', 'fb-editor' ), $position, new ControlId( self::INSET ), new ControlId( self::Z_INDEX ), new ControlId( self::OPACITY ) )
		);
	}
}
