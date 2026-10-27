<?php

declare(strict_types=1);

namespace Fb\CoreBlocks\Library\Button;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Element;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\States\BlockState;
use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\CoreBlocks\CoreBlock;
use Fb\CoreBlocks\ManagedCoreBlock;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;

/**
 * core/button: styling goes on the link (<a>), which core renders as a
 * direct child of the block wrapper.
 */
final class Button extends CoreBlock
{
	public const string LINK = 'link';

	public function managed(): ManagedCoreBlock
	{
		return ManagedCoreBlock::Button;
	}

	protected function define(): BlockDefinition
	{
		$link = new ElementId( self::LINK );

		return new BlockDefinition(
			BlockName::core( 'button' ),
			__( 'Button', 'fb-editor' ),
			new ControlSet( C::background( $link ), C::textColor( $link ), C::fontSize( $link ), C::fontWeight( $link ), C::padding( $link ) ),
			new Layout(
				new Panel( __( 'Colors', 'fb-editor' ), C::id( C::BACKGROUND ), C::id( C::TEXT_COLOR ) ),
				new Panel( __( 'Typography', 'fb-editor' ), C::id( C::FONT_SIZE ), C::id( C::FONT_WEIGHT ) ),
				new Panel( __( 'Spacing', 'fb-editor' ), C::id( C::PADDING ) ),
			),
			// In the editor the link keeps core's class.
			[ new Element( $link, 'wp-block-button__link' ) ],
			BlockState::cases(),
		);
	}

	/**
	 * A link that looks like a button needs a box and no underline.
	 */
	public function baseStyles(): array
	{
		return [
			new BaseStyle(
				DeclarationSet::empty()->with( CssProperty::Display, 'inline-block' )->with( CssProperty::TextDecoration, 'none' ),
				new ElementId( self::LINK )
			),
		];
	}
}
