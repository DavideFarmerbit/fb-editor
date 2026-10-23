<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Condition;

use Fb\Blocks\Values\ResolvedValues;

/**
 * True when every condition is true.
 */
final readonly class All implements Condition
{
	/** @var non-empty-list<Condition> */
	public array $conditions;

	public function __construct( Condition $condition, Condition ...$more )
	{
		$this->conditions = [ $condition, ...array_values( $more ) ];
	}

	public function evaluate( ResolvedValues $values ): bool
	{
		foreach ( $this->conditions as $condition ) {
			if ( ! $condition->evaluate( $values ) ) {
				return false;
			}
		}

		return true;
	}

	public function references(): array
	{
		return array_merge( ...array_map( static fn ( Condition $condition ): array => $condition->references(), $this->conditions ) );
	}

	/**
	 * @return array{kind: 'all', conditions: non-empty-list<Condition>}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'all', 'conditions' => $this->conditions ];
	}
}
