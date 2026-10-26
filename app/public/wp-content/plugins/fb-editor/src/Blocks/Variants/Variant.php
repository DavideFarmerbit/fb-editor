<?php

declare(strict_types=1);

namespace Fb\Blocks\Variants;

use Fb\Blocks\Values\StatefulValues;

/**
 * A named, reusable look of a block type (e.g. a "Primary" button): base
 * and states, between the type defaults and the block's own values.
 */
final readonly class Variant implements \JsonSerializable
{
	public function __construct(
		public VariantSlug $slug,
		public string $name,
		public StatefulValues $values,
	) {
	}

	/**
	 * @return array{slug: VariantSlug, name: string, values: StatefulValues}
	 */
	public function jsonSerialize(): array
	{
		return [ 'slug' => $this->slug, 'name' => $this->name, 'values' => $this->values ];
	}
}
