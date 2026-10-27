<?php

declare(strict_types=1);

namespace Fb\Blocks\Hiding;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\Input\Breakpoints\BreakpointsInput;
use Fb\Blocks\Definition\Panel;
use Fb\Blocks\Definition\Scope;
use Fb\Breakpoints\BreakpointId;
use Fb\Breakpoints\BreakpointList;
use Fb\Css\CssLayer;
use Fb\Output\ClassList;
use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Styles\RuleSet;
use Fb\Styles\Selector;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\BreakpointSet;
use Fb\Values\ResponsiveValue;

/**
 * "Hide on", a setting every block gets: the breakpoints where it isn't
 * shown, each one exactly its own range of widths (hidden on MD says
 * nothing about SM).
 *
 * Not CSS of the block itself: the root gets one class per hidden
 * breakpoint, and one fixed stylesheet (css()) hides each class in its
 * range, in the last layer, so no display setting of the block wins over
 * it. The editor adds the classes too, unless it's showing hidden blocks:
 * then a block hidden at the previewed breakpoint is only faded, so it can
 * still be selected.
 */
final class Hiding
{
	#[ExportToTypeScript]
	public const string CONTROL = 'hideOn';

	/** + breakpoint id: on the root of a block hidden at that breakpoint. */
	#[ExportToTypeScript]
	public const string CLASS_PREFIX = 'fb-hide-';

	/** Editor only: hidden at the previewed breakpoint, shown faded. */
	#[ExportToTypeScript]
	public const string FADED_CLASS = 'fb-hidden-here';

	/** How visible a faded block stays in the editor. */
	private const string FADED_OPACITY = '0.35';

	public static function extend( BlockDefinition $definition ): BlockDefinition
	{
		$id = new ControlId( self::CONTROL );

		return $definition->withControls(
			new ControlSet( new Control( $id, __( 'Hide on', 'fb-editor' ), new BreakpointsInput(), scope: Scope::Static ) ),
			new Panel( __( 'Visibility', 'fb-editor' ), $id )
		);
	}

	/**
	 * The root classes for a block's "Hide on" value (null: not hidden).
	 * Breakpoints the theme doesn't define are ignored.
	 */
	public static function classes( ?ResponsiveValue $value, BreakpointList $breakpoints ): ClassList
	{
		if ( ! $value instanceof BreakpointSet ) {
			return new ClassList();
		}

		$names = [];
		foreach ( $breakpoints as $breakpoint ) {
			if ( $value->has( $breakpoint->id ) ) {
				$names[] = self::className( $breakpoint->id );
			}
		}

		return new ClassList( ...$names );
	}

	public static function className( BreakpointId $breakpoint ): string
	{
		return self::CLASS_PREFIX . $breakpoint->value;
	}

	/**
	 * Hides each breakpoint's class in that breakpoint's range. The editor
	 * also gets the faded look.
	 */
	public static function css( BreakpointList $breakpoints, bool $editor ): string
	{
		$hidden = DeclarationSet::empty()->with( CssProperty::Display, 'none' );
		$css    = '';

		foreach ( $breakpoints as $breakpoint ) {
			$rules = new RuleSet();
			$rules->add( Selector::forClass( self::className( $breakpoint->id ) ), $hidden );

			$query = $breakpoints->bandQuery( $breakpoint );
			$css  .= null === $query ? $rules->toCss() : "@media {$query}{{$rules->toCss()}}";
		}

		if ( $editor ) {
			$faded = new RuleSet();
			$faded->add( Selector::forClass( self::FADED_CLASS ), DeclarationSet::empty()->with( CssProperty::Opacity, self::FADED_OPACITY ) );
			$css .= $faded->toCss();
		}

		return CssLayer::Visibility->wrap( $css );
	}
}
