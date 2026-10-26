<?php

declare(strict_types=1);

namespace Fb\Blocks\Storage;

use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Values\StatefulValues;
use Fb\Blocks\Variants\VariantSet;

/**
 * What the editor needs about one block type's design: the theme's defaults
 * and variants (read-only, from fb.json) and the slug its saved posts have
 * for the active theme (never re-derived in JS).
 */
final readonly class BlockDesignInfo implements \JsonSerializable
{
	public function __construct(
		public BlockName $block,
		public string $postSlug,
		public StatefulValues $themeDefaults,
		public VariantSet $themeVariants,
	) {
	}

	/**
	 * @return array{block: BlockName, postSlug: string, themeDefaults: StatefulValues, themeVariants: VariantSet}
	 */
	public function jsonSerialize(): array
	{
		return [
			'block'         => $this->block,
			'postSlug'      => $this->postSlug,
			'themeDefaults' => $this->themeDefaults,
			'themeVariants' => $this->themeVariants,
		];
	}
}
