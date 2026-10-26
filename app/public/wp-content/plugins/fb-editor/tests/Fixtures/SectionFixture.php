<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Condition\In;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Spacing\SpacingInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Definition\Scope;
use Fb\Blocks\States\BlockState;
use Fb\Styles\CssProperty;
use Fb\Styles\Output\AxisOutput;
use Fb\Styles\Output\BoxOutput;
use Fb\Styles\Output\BoxProperty;
use Fb\Styles\Output\PropertyOutput;

/**
 * A section-like block: static tag, responsive display, padding on the
 * root, and a gap on its inner element shown only for flex/grid. Supports
 * hover and active (declared out of order on purpose), not focus.
 */
final class SectionFixture
{
	public static function definition(): BlockDefinition
	{
		$tag     = new ControlId( 'tag' );
		$display = new ControlId( 'display' );
		$padding = new ControlId( 'padding' );
		$gap     = new ControlId( 'gap' );
		$inner   = new ElementId( 'inner' );

		return new BlockDefinition(
			new BlockName( 'section' ),
			'Section',
			new ControlSet(
				new Control( $tag, 'HTML tag', new SelectInput( Tag::class ), scope: Scope::Static ),
				new Control( $display, 'Display', new SelectInput( Display::class ), new PropertyOutput( CssProperty::Display ), $inner ),
				new Control( $padding, 'Padding', SpacingInput::padding(), new BoxOutput( BoxProperty::Padding ) ),
				new Control( $gap, 'Gap', SpacingInput::gap(), AxisOutput::gap(), $inner, visibleWhen: new In( $display, Display::Flex, Display::Grid ) ),
			),
			new Layout(
				new Panel( 'Layout', $tag, $display, $gap ),
				new Panel( 'Spacing', $padding ),
			),
			[ $inner ],
			[ BlockState::Active, BlockState::Hover ],
		);
	}
}
