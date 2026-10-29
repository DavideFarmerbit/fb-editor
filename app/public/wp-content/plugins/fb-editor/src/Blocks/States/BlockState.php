<?php

declare(strict_types=1);

namespace Fb\Blocks\States;

use Fb\Actions\Library\OpenClose\OpenClose;
use Fb\TypeScript\ExportToTypeScript;

/**
 * Interaction states a block can be styled for, on top of its base look.
 *
 * Case order is cascade order: in the states layers a later state wins, so
 * a hovered button being clicked looks active, the current menu item and an
 * open panel still react to hover, and a disabled button never looks hovered
 * or pressed.
 *
 * States belong to the block as a whole: the pseudo-class goes on the block
 * root and styles reach its elements from there (hovering a button styles
 * its link). The root itself is rarely focusable or a link, so focus,
 * current and disabled also match through its direct child (a button's
 * link) or, for focus, anything inside it.
 */
#[ExportToTypeScript]
enum BlockState: string
{
	/** Points at the page being viewed (aria-current, a menu's current item). */
	case Current  = 'current';
	/** Opened: by an Open / close action, or natively ([open] on <details>, <dialog>). */
	case Open     = 'open';
	case Hover    = 'hover';
	case Focus    = 'focus';
	case Active   = 'active';
	case Disabled = 'disabled';

	public function label(): string
	{
		return match ( $this ) {
			self::Current  => __( 'Current', 'fb-editor' ),
			self::Open     => __( 'Open', 'fb-editor' ),
			self::Hover    => __( 'Hover', 'fb-editor' ),
			self::Focus    => __( 'Focus', 'fb-editor' ),
			self::Active   => __( 'Active', 'fb-editor' ),
			self::Disabled => __( 'Disabled', 'fb-editor' ),
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
		// aria-current="false" is a value too: it means "not current".
		$current = '[aria-current]:not([aria-current=false])';

		return match ( $this ) {
			self::Current  => [ $current, '.current-menu-item', ":has(>{$current})" ],
			self::Open     => [ '[open]', '.' . OpenClose::OPEN_CLASS ],
			self::Hover    => [ ':hover' ],
			self::Focus    => [ ':focus-visible', ':has(:focus-visible)' ],
			self::Active   => [ ':active' ],
			self::Disabled => [ ':disabled', '[aria-disabled=true]', ':has(>[aria-disabled=true])' ],
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
