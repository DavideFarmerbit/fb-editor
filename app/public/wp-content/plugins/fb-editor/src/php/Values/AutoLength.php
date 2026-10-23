<?php

declare(strict_types=1);

namespace Fb\Values;

final readonly class AutoLength implements Length
{
	public function toCss(): string
	{
		return 'auto';
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{kind: 'auto'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'auto' ];
	}
}
