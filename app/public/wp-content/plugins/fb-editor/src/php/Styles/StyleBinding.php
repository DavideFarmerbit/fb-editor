<?php

declare(strict_types=1);

namespace Fb\Styles;

use Fb\Styles\Output\StyleOutput;
use Fb\Values\Responsive;
use Fb\Values\ResponsiveValue;

/**
 * One control's value, applied through its output, to one selector.
 */
final readonly class StyleBinding
{
	public Visibility $visibility;

	/**
	 * @param Responsive<ResponsiveValue> $value
	 */
	public function __construct(
		public Selector $selector,
		public StyleOutput $output,
		public Responsive $value,
		?Visibility $visibility = null,
	) {
		$this->visibility = $visibility ?? Visibility::always();
	}
}
