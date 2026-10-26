<?php

declare(strict_types=1);

namespace Fb\Blocks\Defaults;

use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Values\StatefulValues;

/**
 * What the editor needs about one block type's defaults: the theme's
 * values (read-only, from fb.json) and the slug of the post its saved
 * defaults live in for the active theme (never re-derived in JS).
 */
final readonly class BlockDefaultsInfo implements \JsonSerializable
{
	public function __construct(
		public BlockName $block,
		public string $postSlug,
		public StatefulValues $theme,
	) {
	}

	/**
	 * @return array{block: BlockName, postSlug: string, theme: StatefulValues}
	 */
	public function jsonSerialize(): array
	{
		return [ 'block' => $this->block, 'postSlug' => $this->postSlug, 'theme' => $this->theme ];
	}
}
