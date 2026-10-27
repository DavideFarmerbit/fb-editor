<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A short plain text (an accessible label…): no markup, one line.
 */
final readonly class TextValue implements ResponsiveValue
{
	public function __construct(
		public string $text,
	) {
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	public function jsonSerialize(): string
	{
		return $this->text;
	}
}
