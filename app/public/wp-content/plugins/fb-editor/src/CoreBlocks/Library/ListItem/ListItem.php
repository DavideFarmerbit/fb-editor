<?php

declare(strict_types=1);

namespace Fb\CoreBlocks\Library\ListItem;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\States\BlockState;
use Fb\CoreBlocks\CoreBlock;
use Fb\CoreBlocks\ManagedCoreBlock;

/**
 * core/list-item: one <li> of a List.
 */
final class ListItem extends CoreBlock
{
	public function managed(): ManagedCoreBlock
	{
		return ManagedCoreBlock::ListItem;
	}

	protected function define(): BlockDefinition
	{
		return new BlockDefinition(
			BlockName::core( 'list-item' ),
			__( 'List item', 'fb-editor' ),
			new ControlSet( C::fontSize(), C::fontWeight(), C::lineHeight(), C::letterSpacing(), C::textColor(), C::margin(), C::padding(), ...C::textOptions() ),
			new Layout(
				new Panel( __( 'Typography', 'fb-editor' ), C::id( C::FONT_SIZE ), C::id( C::FONT_WEIGHT ), C::id( C::LINE_HEIGHT ), C::id( C::LETTER_SPACING ), ...C::textOptionIds() ),
				new Panel( __( 'Colors', 'fb-editor' ), C::id( C::TEXT_COLOR ) ),
				new Panel( __( 'Spacing', 'fb-editor' ), C::id( C::MARGIN ), C::id( C::PADDING ) ),
			),
			states: [ BlockState::Hover ],
		);
	}
}
