<?php

declare(strict_types=1);

namespace Fb\CoreBlocks\Library\Buttons;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\Options\Justify;
use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\CoreBlocks\CoreBlock;
use Fb\CoreBlocks\ManagedCoreBlock;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Styles\Output\PropertyOutput;

final class Buttons extends CoreBlock
{
	public const string JUSTIFY = 'justify';

	public function managed(): ManagedCoreBlock
	{
		return ManagedCoreBlock::Buttons;
	}

	protected function define(): BlockDefinition
	{
		return new BlockDefinition(
			BlockName::core( 'buttons' ),
			__( 'Buttons', 'fb-editor' ),
			new ControlSet(
				new Control( new ControlId( self::JUSTIFY ), __( 'Justify', 'fb-editor' ), new SelectInput( Justify::class ), new PropertyOutput( CssProperty::JustifyContent ) ),
				C::gap(),
				C::margin(),
			),
			new Layout(
				new Panel( __( 'Layout', 'fb-editor' ), new ControlId( self::JUSTIFY ), C::id( C::GAP ) ),
				new Panel( __( 'Spacing', 'fb-editor' ), C::id( C::MARGIN ) ),
			),
		);
	}

	/**
	 * Buttons sit in a wrapping row (core's layout support did this).
	 */
	public function baseStyles(): array
	{
		return [
			new BaseStyle( DeclarationSet::empty()->with( CssProperty::Display, 'flex' )->with( CssProperty::FlexWrap, 'wrap' ) ),
		];
	}
}
