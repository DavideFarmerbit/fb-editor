<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\Blocks\Options\BorderStyle;

/**
 * One side of a border: width, style and color, each optional (unset parts
 * are inherited from a larger breakpoint, or not written).
 */
final readonly class BorderSide implements \JsonSerializable
{
	public function __construct(
		public ?CustomLength $width = null,
		public ?BorderStyle $style = null,
		public ?Color $color = null,
	) {
	}

	public function isEmpty(): bool
	{
		return null === $this->width && null === $this->style && null === $this->color;
	}

	/** This side, with the parts it leaves unset taken from `$larger`. */
	public function inheritFrom( ?self $larger ): self
	{
		return null === $larger ? $this : new self( $this->width ?? $larger->width, $this->style ?? $larger->style, $this->color ?? $larger->color );
	}

	/**
	 * Only set parts are written.
	 *
	 * @return array{width?: CustomLength, style?: BorderStyle, color?: Color}
	 */
	public function jsonSerialize(): array
	{
		return array_filter( [ 'width' => $this->width, 'style' => $this->style, 'color' => $this->color ], static fn ( mixed $part ): bool => null !== $part );
	}
}
