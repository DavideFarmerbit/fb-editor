<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * Where a block links to: a URL (sanitized by LinkInput), whether it opens
 * in a new tab, and whether search engines should follow it.
 */
final readonly class LinkValue implements ResponsiveValue
{
	public function __construct(
		public string $url,
		public bool $newTab = false,
		public bool $nofollow = false,
	) {
	}

	/**
	 * The `rel` the link needs: a new tab is always noopener.
	 */
	public function rel(): string
	{
		return trim( ( $this->newTab ? 'noopener ' : '' ) . ( $this->nofollow ? 'nofollow' : '' ) );
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{url: string, newTab: bool, nofollow: bool}
	 */
	public function jsonSerialize(): array
	{
		return [ 'url' => $this->url, 'newTab' => $this->newTab, 'nofollow' => $this->nofollow ];
	}
}
