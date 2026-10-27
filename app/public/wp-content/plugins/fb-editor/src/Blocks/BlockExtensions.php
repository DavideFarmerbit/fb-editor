<?php

declare(strict_types=1);

namespace Fb\Blocks;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Hiding\Hiding;
use Fb\Blocks\Library\Container\ContainerItem;
use Fb\Blocks\Library\Shared\Positioning;

/**
 * The settings every block gets on top of its own (Position, Container
 * item, Hide on), as one complete definition: what everything renders and edits
 * with (BlockRegistry::definitionOf()).
 */
final class BlockExtensions
{
	/** @var \WeakMap<BlockDefinition, BlockDefinition>|null Per block's own definition (itself memoized). */
	private static ?\WeakMap $complete = null;

	public static function complete( BlockDefinition $own ): BlockDefinition
	{
		self::$complete ??= new \WeakMap();

		return self::$complete[ $own ] ??= Hiding::extend( ContainerItem::extend( Positioning::extend( $own ) ) );
	}
}
