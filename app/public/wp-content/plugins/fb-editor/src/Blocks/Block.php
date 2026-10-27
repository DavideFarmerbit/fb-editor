<?php

declare(strict_types=1);

namespace Fb\Blocks;

use Fb\Blocks\BaseStyles\BaseStyle;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Rendering\RenderContext;

/**
 * Base class of every fb block: a definition (controls, layout, elements)
 * and a server-side render. The editor markup lives in a matching TSX file.
 */
abstract class Block implements BlockType
{
	private ?BlockDefinition $definition = null;

	final public function definition(): BlockDefinition
	{
		return $this->definition ??= $this->define();
	}

	/**
	 * Whether the block contains other blocks (rendered into $context->content).
	 */
	public function hasInnerBlocks(): bool
	{
		return false;
	}

	/**
	 * @return list<BaseStyle>
	 */
	public function baseStyles(): array
	{
		return [];
	}

	abstract protected function define(): BlockDefinition;

	/**
	 * Returns safe HTML: escape everything that isn't $context->content.
	 */
	abstract public function render( RenderContext $context ): string;
}
