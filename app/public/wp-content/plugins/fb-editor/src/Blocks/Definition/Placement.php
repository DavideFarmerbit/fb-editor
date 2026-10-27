<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

use Fb\Blocks\Definition\Condition\Condition;

/**
 * Where a control applies: only while its block sits directly inside a
 * `parent` block whose values satisfy `when` (at the inspected breakpoint),
 * e.g. flex-grow only inside a Container laid out as flex.
 *
 * Such controls belong to the block's place, not its look: they never vary
 * per state, and type defaults and variants never hold them.
 */
final readonly class Placement implements \JsonSerializable
{
	public function __construct(
		public BlockName $parent,
		/** Evaluated against the parent's resolved values. */
		public Condition $when,
	) {
	}

	/**
	 * @return array{parent: BlockName, when: Condition}
	 */
	public function jsonSerialize(): array
	{
		return [ 'parent' => $this->parent, 'when' => $this->when ];
	}
}
