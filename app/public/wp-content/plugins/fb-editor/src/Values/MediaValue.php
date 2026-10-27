<?php

declare(strict_types=1);

namespace Fb\Values;

/**
 * A media library item, by attachment id: the library stays the single
 * source of its URL, sizes and alt text.
 */
final readonly class MediaValue implements ResponsiveValue
{
	public function __construct(
		public int $id,
		public MediaKind $kind,
	) {
		if ( $id < 1 ) {
			throw new \InvalidArgumentException( "Invalid attachment id {$id}." );
		}
	}

	public function inheritFrom( ResponsiveValue $larger ): static
	{
		return $this;
	}

	/**
	 * @return array{id: int, kind: MediaKind}
	 */
	public function jsonSerialize(): array
	{
		return [ 'id' => $this->id, 'kind' => $this->kind ];
	}
}
