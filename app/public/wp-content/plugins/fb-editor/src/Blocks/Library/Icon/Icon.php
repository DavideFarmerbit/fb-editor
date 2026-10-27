<?php

declare(strict_types=1);

namespace Fb\Blocks\Library\Icon;

use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\Blocks\Block;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Link\LinkInput;
use Fb\Blocks\Definition\Input\Svg\SvgInput;
use Fb\Blocks\Definition\Input\Text\TextInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Definition\Scope;
use Fb\Blocks\Library\Shared\CommonControls as C;
use Fb\Blocks\Rendering\RenderContext;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\LinkValue;
use Fb\Values\SvgValue;
use Fb\Values\TextValue;

/**
 * A pasted SVG icon, optionally a link:
 *
 *   <span class="fb-icon" role="img" aria-label="…"><svg>…</svg></span>
 *   <a class="fb-icon" href="…" aria-label="…"><svg>…</svg></a>
 *
 * The SVG fills the block's box: 1em square by default (it sits in text
 * like a glyph). Setting only the width keeps it square; a height too
 * overrides that. Icons drawn with currentColor follow the Color setting.
 */
final class Icon extends Block
{
	#[ExportToTypeScript]
	public const string NAME = 'icon';
	#[ExportToTypeScript]
	public const string SVG = 'svg';
	#[ExportToTypeScript]
	public const string LABEL = 'label';
	#[ExportToTypeScript]
	public const string LINK = 'link';

	protected function define(): BlockDefinition
	{
		$id = static fn ( string $value ): ControlId => new ControlId( $value );

		return new BlockDefinition(
			new BlockName( self::NAME ),
			__( 'Icon', 'fb-editor' ),
			new ControlSet(
				new Control( $id( self::SVG ), __( 'SVG code', 'fb-editor' ), new SvgInput(), scope: Scope::Static, alwaysShown: true ),
				new Control( $id( self::LABEL ), __( 'Accessible label', 'fb-editor' ), new TextInput(), scope: Scope::Static ),
				new Control( $id( self::LINK ), __( 'Link', 'fb-editor' ), new LinkInput(), scope: Scope::Static ),
				C::textColor(),
				C::background(),
				C::padding(),
				C::margin(),
				C::shadow(),
				...C::size(),
				...C::border(),
			),
			new Layout(
				new Panel( __( 'Icon', 'fb-editor' ), $id( self::SVG ), $id( self::LABEL ), $id( self::LINK ) ),
				new Panel( __( 'Size', 'fb-editor' ), ...C::sizeIds() ),
				new Panel( __( 'Colors', 'fb-editor' ), C::id( C::TEXT_COLOR ), C::id( C::BACKGROUND ) ),
				new Panel( __( 'Spacing', 'fb-editor' ), C::id( C::PADDING ), C::id( C::MARGIN ) ),
				new Panel(
					__( 'Border & shadow', 'fb-editor' ),
					C::id( C::BORDER_WIDTH ),
					C::id( C::BORDER_STYLE ),
					C::id( C::BORDER_COLOR ),
					C::id( C::BORDER_RADIUS ),
					C::id( C::SHADOW )
				),
			),
		);
	}

	/**
	 * A box the SVG fills: 1em wide and square until a height is set (an
	 * explicit height wins over aspect-ratio).
	 */
	public function baseStyles(): array
	{
		return [
			new BaseStyle(
				DeclarationSet::empty()
					->with( CssProperty::Display, 'inline-flex' )
					->with( CssProperty::Width, '1em' )
					->with( CssProperty::AspectRatio, '1/1' )
					->with( CssProperty::LineHeight, '0' )
			),
			new BaseStyle(
				DeclarationSet::empty()->with( CssProperty::Display, 'block' )->with( CssProperty::Width, '100%' )->with( CssProperty::Height, '100%' ),
				descendantTag: 'svg'
			),
		];
	}

	public function render( RenderContext $context ): string
	{
		$svg = $context->value( new ControlId( self::SVG ) );
		if ( ! $svg instanceof SvgValue ) {
			return '';
		}

		$label = $context->value( new ControlId( self::LABEL ) );
		$link  = $context->value( new ControlId( self::LINK ) );
		$aria  = $label instanceof TextValue ? sprintf( ' aria-label="%s"', esc_attr( $label->text ) ) : '';

		if ( $link instanceof LinkValue ) {
			return sprintf(
				'<a class="%s" href="%s"%s%s%s>%s</a>',
				$context->rootClassAttribute(),
				esc_url( $link->url ),
				$link->newTab ? ' target="_blank"' : '',
				'' === $link->rel() ? '' : sprintf( ' rel="%s"', esc_attr( $link->rel() ) ),
				$aria,
				$svg->markup
			);
		}

		return sprintf(
			'<span class="%s"%s>%s</span>',
			$context->rootClassAttribute(),
			'' === $aria ? ' aria-hidden="true"' : ' role="img"' . $aria,
			$svg->markup
		);
	}
}
