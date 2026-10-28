<?php

declare(strict_types=1);

namespace Fb\Actions\Library;

use Fb\Actions\Effect;
use Fb\Actions\Param;
use Fb\Actions\ParamValues;
use Fb\Blocks\Definition\Input\Link\LinkInput;
use Fb\Blocks\Definition\Input\Text\TextInput;
use Fb\Values\LinkValue;
use Fb\Values\TextValue;

/**
 * The whole block is a link (a card…): a real <a> laid over it, rendered on
 * the server, no script. Links and buttons inside the block stay clickable
 * on top of it. Its accessible name is the label, else the block's text.
 */
final class Link extends Effect
{
	public const string LINK  = 'link';
	public const string LABEL = 'label';

	/** On the <a>; base CSS lays it over its block. */
	public const string CLASS_NAME = 'fb-link';

	/** Accessible names longer than this are cut. */
	private const int MAX_NAME = 120;

	public function name(): string
	{
		return 'link';
	}

	public function label(): string
	{
		return __( 'Link', 'fb-editor' );
	}

	public function params(): array
	{
		return [
			new Param( self::LINK, __( 'Link', 'fb-editor' ), new LinkInput(), required: true ),
			new Param( self::LABEL, __( 'Accessible label', 'fb-editor' ), new TextInput() ),
		];
	}

	public function runsOnPage(): bool
	{
		return false;
	}

	public function markup( ParamValues $params, string $text ): ?string
	{
		$link = $params->get( self::LINK );
		if ( ! $link instanceof LinkValue ) {
			return null;
		}

		$label = $params->get( self::LABEL );
		$name  = $label instanceof TextValue ? $label->text : mb_substr( $text, 0, self::MAX_NAME );

		return sprintf(
			'<a class="%s" href="%s"%s%s%s></a>',
			esc_attr( self::CLASS_NAME ),
			esc_url( $link->url ),
			$link->newTab ? ' target="_blank"' : '',
			'' === $link->rel() ? '' : sprintf( ' rel="%s"', esc_attr( $link->rel() ) ),
			'' === $name ? '' : sprintf( ' aria-label="%s"', esc_attr( $name ) )
		);
	}
}
