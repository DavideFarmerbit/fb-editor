<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * On/off. Not a CSS value by itself: an output (e.g. ToggleOutput) decides
 * what each state means.
 */
final readonly class BooleanValue implements ResponsiveValue
{
	public function __construct(
		public bool $on,
	) {
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	public function jsonSerialize(): bool
	{
		return $this->on;
	}
}
