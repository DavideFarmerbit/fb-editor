<?php

declare(strict_types=1);

namespace Fb\Breakpoints;

/**
 * One breakpoint. The base breakpoint has no maxWidth (no media query);
 * every other one applies at `max-width: maxWidth px`.
 */
final readonly class Breakpoint implements \JsonSerializable
{
	public function __construct(
		public BreakpointId $id,
		public string $label,
		public ?BreakpointIcon $icon = null,
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

	/**
	 * @return array{id: BreakpointId, label: string, icon: ?string, maxWidth: ?int, previewWidth: ?int}
	 */
	public function jsonSerialize(): array
	{
		return [
			'id'           => $this->id,
			'label'        => $this->label,
			'icon'         => $this->icon?->value,
			'maxWidth'     => $this->maxWidth,
			'previewWidth' => $this->previewWidth,
		];
	}
}
