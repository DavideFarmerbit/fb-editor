<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Target;

use Fb\Actions\Anchor\BlockAnchor;
use Fb\Values\ResponsiveValue;

/**
 * Which block an action works on: the block itself, or another one by its
 * anchor.
 *
 *   "self"  |  {"block": "a7k2b9"}
 */
final readonly class TargetValue implements ResponsiveValue
{
	public function __construct(
		/** Null: the block itself. */
		public ?BlockAnchor $block = null,
	) {
	}

	public function isSelf(): bool
	{
		return null === $this->block;
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return 'self'|array{block: BlockAnchor}
	 */
	public function jsonSerialize(): string|array
	{
		return null === $this->block ? 'self' : [ 'block' => $this->block ];
	}
}
