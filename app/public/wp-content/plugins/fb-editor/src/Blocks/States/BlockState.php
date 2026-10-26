<?php

declare(strict_types=1);

namespace Fb\Blocks\States;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Interaction states a block can be styled for, on top of its base look.
 *
 * Case order is cascade order: in the states layers a later state wins, so
 * a hovered button being clicked looks active.
 *
 * States belong to the block as a whole: the pseudo-class goes on the block
 * root and styles reach its elements from there (hovering a button styles
 * its link). The root itself is rarely focusable, so focus means "it, or
 * something inside it, has visible focus".
 */
#[ExportToTypeScript]
enum BlockState: string
{
	case Hover  = 'hover';
	case Focus  = 'focus';
	case Active = 'active';

	public function label(): string
	{
		return match ( $this ) {
			self::Hover  => __( 'Hover', 'fb-editor' ),
			self::Focus  => __( 'Focus', 'fb-editor' ),
			self::Active => __( 'Active', 'fb-editor' ),
		};
	}

	/** Appended to the root's class selector. */
	public function pseudoClass(): string
	{
		return match ( $this ) {
			self::Hover  => ':hover',
			self::Focus  => ':is(:focus-visible,:has(:focus-visible))',
			self::Active => ':active',
		};
	}

	/**
	 * In cascade (case) order, without duplicates.
	 *
	 * @param list<self> $states
	 * @return list<self>
	 */
	public static function ordered( array $states ): array
	{
		return array_values( array_filter( self::cases(), static fn ( self $state ): bool => in_array( $state, $states, true ) ) );
	}
}
