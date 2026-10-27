<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Values\BlockAttribute;
use Fb\Blocks\Variants\Variant;
use Fb\Output\ClassList;

/**
 * One rendered block's styling: its parsed attribute, the variant it uses
 * (if that exists), and the fb classes its root gets.
 */
final readonly class StyledBlock
{
	public function __construct(
		public BlockAttribute $attribute,
		public ?Variant $variant,
		/** Block class, variant class, instance class (if it has CSS), hide classes. */
		public ClassList $classes,
	) {
	}
}
