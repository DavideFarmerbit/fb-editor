<?php

declare(strict_types=1);

namespace Fb\Blocks\Variants;

use Fb\Blocks\Definition\BlockDefinition;

/**
 * A variant some block on the page uses: its CSS gets printed.
 */
final readonly class UsedVariant
{
	public function __construct(
		public BlockDefinition $definition,
		public Variant $variant,
	) {
	}

	public function className(): string
	{
		return $this->definition->name->variantClass( $this->variant->slug );
	}
}
