<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Condition;

use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Values\ResolvedValues;

/**
 * True when a control has a value at this breakpoint (own or inherited).
 */
final readonly class HasValue implements Condition
{
	public function __construct(
		public ControlId $control,
	) {
	}

	public function evaluate( ResolvedValues $values ): bool
	{
		return null !== $values->get( $this->control );
	}

	public function references(): array
	{
		return [ $this->control ];
	}

	/**
	 * @return array{kind: 'hasValue', control: ControlId}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'hasValue', 'control' => $this->control ];
	}
}
