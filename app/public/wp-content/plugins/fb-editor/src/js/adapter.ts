/**
 * Every dependency on WordPress internals lives here (and in adapter.scss,
 * and Fb\Adapter\WpInternals on the PHP side).
 * After a WP update, if something in the editor UI breaks, start here.
 *
 * Verified against WordPress 7.1.3.
 */

/**
 * Slot name behind the (private since 7.x) `PinnedItems` component that
 * renders on the right side of the editor header, in both the post and
 * site editors. A public `<Fill name={ PINNED_ITEMS_SLOT }>` targets it.
 */
export const PINNED_ITEMS_SLOT = 'PinnedItems/core';
