<?php

declare(strict_types=1);

namespace Fb\Blocks\Looks;

use Fb\Blocks\Values\BlockValues;

/**
 * One look of a source (a block, a type default, a variant): which look,
 * and the values it sets.
 *
 *   {"states": ["hover"], "when": ["dark"], "values": {controlId: …}}
 */
final readonly class Look implements \JsonSerializable
{
	public function __construct(
		public LookKey $key,
		public BlockValues $values,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array
	{
		return [ ...$this->key->jsonSerialize(), 'values' => $this->values ];
	}
}
