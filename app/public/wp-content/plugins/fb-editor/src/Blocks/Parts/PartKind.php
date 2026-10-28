<?php

declare(strict_types=1);

namespace Fb\Blocks\Parts;

use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\Library\Shared\Positioning;
use Fb\Blocks\Options\PseudoDisplay;
use Fb\Styles\CssProperty;
use Fb\Styles\Output\PropertyOutput;
use Fb\TypeScript\ExportToTypeScript;

/**
 * What a part is, and so which settings it offers: the same for every
 * part of a kind, on every block.
 */
#[ExportToTypeScript]
enum PartKind: string
{
	/** A generated box (::before, ::after): any look, drawn from its content. */
	case Pseudo = 'pseudo';
	/** A real inner element (an image, a background video): its box only. */
	case Element = 'element';
	/** A list marker: CSS allows only text styling there. */
	case Marker = 'marker';

	/** Name of the ::before / ::after layout setting. */
	public const string DISPLAY = 'display';

	public function controls(): ControlSet
	{
		return match ( $this ) {
			self::Pseudo  => new ControlSet( ...[
				C::content(),
				new Control( new ControlId( self::DISPLAY ), __( 'Display', 'fb-editor' ), new SelectInput( PseudoDisplay::class ), new PropertyOutput( CssProperty::Display ) ),
				...C::size(),
				...C::typography(),
				C::textColor(),
				C::background(),
				C::gradient(),
				C::shadow(),
				...C::border(),
				...Positioning::controls(),
			] ),
			self::Element => new ControlSet( ...[
				...C::size(),
				C::aspectRatio(),
				C::objectFit(),
				C::shadow(),
				Positioning::opacity(),
				...C::border(),
			] ),
			self::Marker  => new ControlSet( ...[
				C::content(),
				C::textColor(),
				C::fontFamily(),
				C::fontSize(),
				C::fontWeight(),
			] ),
		};
	}

	public function layout(): Layout
	{
		return match ( $this ) {
			self::Pseudo  => new Layout(
				new Panel( __( 'Content', 'fb-editor' ), C::id( C::CONTENT ) ),
				new Panel( __( 'Size', 'fb-editor' ), ...[ new ControlId( self::DISPLAY ), ...C::sizeIds() ] ),
				new Panel( __( 'Typography', 'fb-editor' ), ...C::typographyIds() ),
				new Panel( __( 'Colors', 'fb-editor' ), C::id( C::TEXT_COLOR ), C::id( C::BACKGROUND ), C::id( C::GRADIENT ) ),
				new Panel( __( 'Border & shadow', 'fb-editor' ), C::id( C::BORDER ), C::id( C::BORDER_RADIUS ), C::id( C::SHADOW ) ),
				Positioning::panel(),
			),
			self::Element => new Layout(
				new Panel( __( 'Size', 'fb-editor' ), ...[ ...C::sizeIds(), C::id( C::ASPECT_RATIO ), C::id( C::OBJECT_FIT ) ] ),
				new Panel( __( 'Border & shadow', 'fb-editor' ), C::id( C::BORDER ), C::id( C::BORDER_RADIUS ), C::id( C::SHADOW ) ),
				new Panel( __( 'Effects', 'fb-editor' ), new ControlId( Positioning::OPACITY ) ),
			),
			self::Marker  => new Layout(
				new Panel( __( 'Marker', 'fb-editor' ), C::id( C::CONTENT ), C::id( C::TEXT_COLOR ), C::id( C::FONT_FAMILY ), C::id( C::FONT_SIZE ), C::id( C::FONT_WEIGHT ) ),
			),
		};
	}
}
