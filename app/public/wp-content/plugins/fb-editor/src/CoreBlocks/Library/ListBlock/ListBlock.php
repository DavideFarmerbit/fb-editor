<?php

declare(strict_types=1);

namespace Fb\CoreBlocks\Library\ListBlock;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\Parts\Part;
use Fb\Blocks\States\BlockState;
use Fb\CoreBlocks\CoreBlock;
use Fb\CoreBlocks\ManagedCoreBlock;

/**
 * core/list ("List" is a reserved word in PHP).
 */
final class ListBlock extends CoreBlock
{
	public function managed(): ManagedCoreBlock
	{
		return ManagedCoreBlock::List;
	}

	protected function define(): BlockDefinition
	{
		return new BlockDefinition(
			BlockName::core( 'list' ),
			__( 'List', 'fb-editor' ),
			new ControlSet( C::textColor(), C::margin(), C::padding(), ...C::typography() ),
			new Layout(
				new Panel( __( 'Typography', 'fb-editor' ), ...C::typographyIds() ),
				new Panel( __( 'Colors', 'fb-editor' ), C::id( C::TEXT_COLOR ) ),
				new Panel( __( 'Spacing', 'fb-editor' ), C::id( C::MARGIN ), C::id( C::PADDING ) ),
			),
			states: [ BlockState::Hover ],
			parts: [ Part::itemMarkers() ],
		);
	}
}
