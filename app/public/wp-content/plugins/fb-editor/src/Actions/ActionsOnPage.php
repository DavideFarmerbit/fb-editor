<?php

declare(strict_types=1);

namespace Fb\Actions;

use Fb\Actions\Library\Link;
use Fb\Actions\Library\OpenClose;
use Fb\Css\CssLayer;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Styles\RuleSet;
use Fb\Styles\Selector;

/**
 * What the rendered page's actions need, gathered while its blocks render
 * (block themes render the whole template before <head>): the targets
 * hidden until opened, whether some block is a link, whether the script is
 * needed at all.
 */
final class ActionsOnPage
{
	/** @var array<string, TargetHiding> Keyed by anchor. */
	private array $hidden = [];

	private bool $links = false;

	private bool $runtime = false;

	public function hideUntilOpen( BlockAnchor $target, TargetHiding $hiding ): void
	{
		// Several rules may open the same target: keeping its space wins
		// only if they all ask for it.
		$this->hidden[ $target->value ] = TargetHiding::Removed === ( $this->hidden[ $target->value ] ?? $hiding ) ? TargetHiding::Removed : $hiding;
	}

	public function hasLink(): void
	{
		$this->links = true;
	}

	public function needsRuntime(): void
	{
		$this->runtime = true;
	}

	public function usesRuntime(): bool
	{
		return $this->runtime;
	}

	/**
	 * Links laid over their block (fb.base: any block setting wins), and
	 * closed targets hidden (fb.visibility: whatever display they have).
	 */
	public function css(): string
	{
		$css = '';

		if ( $this->links ) {
			$base = new RuleSet();
			$base->add( Selector::forClass( Link::CLASS_NAME ), self::linkBox() );
			$link = '.' . Link::CLASS_NAME;
			$css .= CssLayer::Base->wrap(
				":where(:has(>{$link})){position:relative;}"
				. $base->toCss()
				// The block's own links and buttons stay clickable, above it.
				. ":where(:has(>{$link}) :is(a,button):not({$link})){position:relative;z-index:2;}"
			);
		}

		if ( [] !== $this->hidden ) {
			$rules = new RuleSet();
			foreach ( $this->hidden as $anchor => $hiding ) {
				$rules->add(
					Selector::forClass( ( new BlockAnchor( $anchor ) )->className() )->withPseudoClass( ':not(.' . OpenClose::OPEN_CLASS . ',[open])' ),
					TargetHiding::Removed === $hiding
						? DeclarationSet::empty()->with( CssProperty::Display, 'none' )
						: DeclarationSet::empty()->with( CssProperty::Visibility, 'hidden' )
				);
			}
			$css .= CssLayer::Visibility->wrap( $rules->toCss() );
		}

		return $css;
	}

	/** The link covers its block, under the block's own links and buttons. */
	private static function linkBox(): DeclarationSet
	{
		return DeclarationSet::empty()
			->with( CssProperty::Position, 'absolute' )
			->with( CssProperty::Top, '0' )
			->with( CssProperty::Right, '0' )
			->with( CssProperty::Bottom, '0' )
			->with( CssProperty::Left, '0' )
			->with( CssProperty::ZIndex, '1' );
	}
}
