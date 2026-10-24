<?php

declare(strict_types=1);

namespace Fb\Breakpoints;

/**
 * One breakpoint. The base breakpoint has no maxWidth (no media query);
 * every other one applies at `max-width: maxWidth px`.
 */
final readonly class Breakpoint
{
	public function __construct(
		public BreakpointId $id,
		/** Short label shown on the switcher, e.g. "LG". */
		public string $label,
		public ?int $maxWidth = null,
		public ?int $previewWidth = null,
	) {
		if ( null !== $maxWidth && $maxWidth < 1 ) {
			throw new \InvalidArgumentException( "Breakpoint \"{$id}\": maxWidth must be positive." );
		}
		if ( null !== $previewWidth && $previewWidth < 1 ) {
			throw new \InvalidArgumentException( "Breakpoint \"{$id}\": previewWidth must be positive." );
		}
	}

	public function isBase(): bool
	{
		return null === $this->maxWidth;
	}

	public function mediaQuery(): ?string
	{
		return null === $this->maxWidth ? null : "(max-width: {$this->maxWidth}px)";
	}
}
