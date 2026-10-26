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
		$matches = $this->matches();

		return 1 === count( $matches ) ? $matches[0] : ':is(' . implode( ',', $matches ) . ')';
	}

	/**
	 * Editor only: added to the selected block's root while this state is
	 * inspected, so the canvas shows the state without interacting.
	 */
	public function previewClass(): string
	{
		return 'fb-preview-' . $this->value;
	}

	/** The editor's selector: the real state, or the preview class. */
	public function editorPseudoClass(): string
	{
		return ':is(' . implode( ',', [ ...$this->matches(), '.' . $this->previewClass() ] ) . ')';
	}

	/**
	 * @return non-empty-list<string>
	 */
	private function matches(): array
	{
		return match ( $this ) {
			self::Hover  => [ ':hover' ],
			self::Focus  => [ ':focus-visible', ':has(:focus-visible)' ],
			self::Active => [ ':active' ],
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
