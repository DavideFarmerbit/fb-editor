<?php

declare(strict_types=1);

namespace Fb\Blocks;

use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\Blocks\Definition\BlockDefinition;

/**
 * Any block type fb defines controls for: an fb block or a managed core
 * block.
 */
interface BlockType
{
	public function definition(): BlockDefinition;

	/**
	 * Functional CSS the type needs before any setting, in `fb.base`.
	 *
	 * @return list<BaseStyle>
	 */
	public function baseStyles(): array;
}
