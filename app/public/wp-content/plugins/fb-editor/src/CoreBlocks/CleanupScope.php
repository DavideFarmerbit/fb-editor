<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

/**
 * Which tags of a block's rendered HTML the markup cleaner may touch.
 *
 * Blocks containing inner blocks must never use Subtree: their HTML includes
 * the inner blocks' markup, which may belong to blocks fb doesn't manage.
 */
enum CleanupScope
{
	/** Only the wrapper tag (blocks with inner blocks). */
	case Root;
	/** Wrapper + one item tag per post (post template's <li>s). */
	case RootAndPostItems;
	/** Every tag (leaf blocks: all HTML is the block's own). */
	case Subtree;
}
