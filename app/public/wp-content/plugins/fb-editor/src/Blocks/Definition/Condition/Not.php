<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Condition;

use Fb\Blocks\Values\ResolvedValues;

final readonly class Not implements Condition
{
	public function __construct(
		public Condition $condition,
	) {
	}

	public function evaluate( ResolvedValues $values ): bool
	{
		return ! $this->condition->evaluate( $values );
	}

	public function references(): array
	{
		return $this->condition->references();
	}

	/**
	 * @return array{kind: 'not', condition: Condition}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'not', 'condition' => $this->condition ];
	}
}
