<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

use Fb\Blocks\Definition\BlockDefinition;

/**
 * A core block fb manages: its definition (controls, layout, elements) and
 * the functional base CSS that replaces core's stylesheet. Core renders the
 * markup; fb adds classes and CSS.
 */
abstract class CoreBlock
{
	private ?BlockDefinition $definition = null;

	final public function definition(): BlockDefinition
	{
		return $this->definition ??= $this->define();
	}

	abstract public function managed(): ManagedCoreBlock;

	abstract protected function define(): BlockDefinition;

	/**
	 * @return list<BaseStyle>
	 */
	public function baseStyles(): array
	{
		return [];
	}
}
