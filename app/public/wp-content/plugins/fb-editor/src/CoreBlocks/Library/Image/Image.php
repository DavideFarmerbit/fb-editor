<?php

declare(strict_types=1);

namespace Fb\CoreBlocks\Library\Image;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\Blocks\Parts\Part;
use Fb\Blocks\States\BlockState;
use Fb\CoreBlocks\CoreBlock;
use Fb\CoreBlocks\ManagedCoreBlock;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Styles\Output\PropertyOutput;

final class Image extends CoreBlock
{
	public const string MAX_WIDTH = 'maxWidth';

	public function managed(): ManagedCoreBlock
	{
		return ManagedCoreBlock::Image;
	}

	protected function define(): BlockDefinition
	{
		return new BlockDefinition(
			BlockName::core( 'image' ),
			__( 'Image', 'fb-editor' ),
			new ControlSet(
				new Control( new ControlId( self::MAX_WIDTH ), __( 'Max width', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::MaxWidth ) ),
				C::margin(),
			),
			new Layout(
				new Panel( __( 'Size', 'fb-editor' ), new ControlId( self::MAX_WIDTH ) ),
				new Panel( __( 'Spacing', 'fb-editor' ), C::id( C::MARGIN ) ),
			),
			states: [ BlockState::Hover ],
			parts: [ Part::tag( 'image', __( 'Image', 'fb-editor' ), 'img' ) ],
		);
	}

	/**
	 * What core's image CSS provided that pages rely on: images never
	 * overflow their container and keep their ratio; no browser figure
	 * margins.
	 */
	public function baseStyles(): array
	{
		return [
			new BaseStyle(
				DeclarationSet::empty()
					->with( CssProperty::MarginTop, '0' )
					->with( CssProperty::MarginRight, '0' )
					->with( CssProperty::MarginBottom, '0' )
					->with( CssProperty::MarginLeft, '0' )
			),
			new BaseStyle(
				DeclarationSet::empty()
					->with( CssProperty::MaxWidth, '100%' )
					->with( CssProperty::Height, 'auto' )
					->with( CssProperty::VerticalAlign, 'middle' ),
				descendantTag: 'img'
			),
		];
	}
}
