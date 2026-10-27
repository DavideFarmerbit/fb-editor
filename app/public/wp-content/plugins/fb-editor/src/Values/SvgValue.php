<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * SVG markup, safe to print as is: only ever built by SvgSanitizer.
 */
final readonly class SvgValue implements ResponsiveValue
{
	public function __construct(
		public string $markup,
	) {
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	public function jsonSerialize(): string
	{
		return $this->markup;
	}
}
