<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Separator;

use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\Blocks\Block;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\Color\ColorInput;
use Fb\Blocks\Definition\Input\Length\LengthInput;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Svg\SvgInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Definition\Scope;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\Options\BorderStyle;
use Fb\Blocks\Rendering\RenderContext;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Styles\Output\PropertyOutput;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\SvgValue;

/**
 * A horizontal rule, optionally with an icon in the middle:
 *
 *   <div class="fb-separator" role="separator">
 *     <span class="fb-separator__line"></span>
 *     <span class="fb-separator__icon"><svg>…</svg></span>   (with an icon)
 *     <span class="fb-separator__line"></span>                 (with an icon)
 *   </div>
 */
final class Separator extends Block
{
	#[ExportToTypeScript]
	public const string NAME = 'separator';
	#[ExportToTypeScript]
	public const string SVG = 'svg';
	#[ExportToTypeScript]
	public const string LINE = 'line';
	#[ExportToTypeScript]
	public const string ICON = 'icon';

	public const string LINE_WIDTH = 'lineWidth';
	public const string LINE_STYLE = 'lineStyle';
	public const string LINE_COLOR = 'lineColor';
	public const string ICON_SIZE  = 'iconSize';
	public const string GAP        = 'gap';

	protected function define(): BlockDefinition
	{
		$id   = static fn ( string $value ): ControlId => new ControlId( $value );
		$line = new ElementId( self::LINE );
		$icon = new ElementId( self::ICON );

		return new BlockDefinition(
			new BlockName( self::NAME ),
			__( 'Separator', 'fb-editor' ),
			new ControlSet(
				new Control( $id( self::SVG ), __( 'Icon (SVG code)', 'fb-editor' ), new SvgInput(), scope: Scope::Static ),
				new Control( $id( self::LINE_WIDTH ), __( 'Thickness', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::BorderTopWidth ), $line ),
				new Control( $id( self::LINE_STYLE ), __( 'Style', 'fb-editor' ), new SelectInput( BorderStyle::class ), new PropertyOutput( CssProperty::BorderTopStyle ), $line ),
				new Control( $id( self::LINE_COLOR ), __( 'Line color', 'fb-editor' ), new ColorInput(), new PropertyOutput( CssProperty::BorderTopColor ), $line ),
				new Control( $id( self::ICON_SIZE ), __( 'Icon size', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::Width, CssProperty::Height ), $icon ),
				new Control( $id( self::GAP ), __( 'Space around the icon', 'fb-editor' ), new LengthInput(), new PropertyOutput( CssProperty::ColumnGap ) ),
				C::textColor(),
				C::margin(),
			),
			new Layout(
				new Panel( __( 'Line', 'fb-editor' ), $id( self::LINE_WIDTH ), $id( self::LINE_STYLE ), $id( self::LINE_COLOR ) ),
				new Panel( __( 'Icon', 'fb-editor' ), $id( self::SVG ), $id( self::ICON_SIZE ), $id( self::GAP ), C::id( C::TEXT_COLOR ) ),
				new Panel( __( 'Spacing', 'fb-editor' ), C::id( C::MARGIN ) ),
			),
			[ $line, $icon ],
		);
	}

	/**
	 * Lines share the space left by the icon; drawn as a top border in the
	 * text color until set otherwise.
	 */
	public function baseStyles(): array
	{
		return [
			new BaseStyle( DeclarationSet::empty()->with( CssProperty::Display, 'flex' )->with( CssProperty::AlignItems, 'center' )->with( CssProperty::ColumnGap, '1em' ) ),
			new BaseStyle(
				DeclarationSet::empty()
					->with( CssProperty::FlexGrow, '1' )
					->with( CssProperty::FlexBasis, '0' )
					->with( CssProperty::BorderTopWidth, '1px' )
					->with( CssProperty::BorderTopStyle, 'solid' )
					->with( CssProperty::BorderTopColor, 'currentColor' ),
				new ElementId( self::LINE )
			),
			new BaseStyle(
				DeclarationSet::empty()->with( CssProperty::Display, 'inline-flex' )->with( CssProperty::Width, '1.5em' )->with( CssProperty::Height, '1.5em' ),
				new ElementId( self::ICON )
			),
			new BaseStyle(
				DeclarationSet::empty()->with( CssProperty::Display, 'block' )->with( CssProperty::Width, '100%' )->with( CssProperty::Height, '100%' ),
				new ElementId( self::ICON ),
				'svg'
			),
		];
	}

	public function render( RenderContext $context ): string
	{
		$line = sprintf( '<span class="%s"></span>', $context->elementClassAttribute( new ElementId( self::LINE ) ) );
		$svg  = $context->value( new ControlId( self::SVG ) );

		$inner = $svg instanceof SvgValue
			? $line . sprintf( '<span class="%s" aria-hidden="true">%s</span>', $context->elementClassAttribute( new ElementId( self::ICON ) ), $svg->markup ) . $line
			: $line;

		return sprintf( '<div class="%s" role="separator">%s</div>', $context->rootClassAttribute(), $inner );
	}
}
