<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Condition;

use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\Input\LabeledOption;
use Fb\Blocks\Values\ResolvedValues;
use Fb\Values\KeywordValue;

/**
 * True when a select control's resolved value is the given option.
 */
final readonly class Equals implements Condition
{
	public function __construct(
		public ControlId $control,
		public LabeledOption $option,
	) {
	}

	public function evaluate( ResolvedValues $values ): bool
	{
		$value = $values->get( $this->control );

		return $value instanceof KeywordValue && $value->keyword === (string) $this->option->value;
	}

	public function references(): array
	{
		return [ $this->control ];
	}

	/**
	 * @return array{kind: 'equals', control: ControlId, value: int|string}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'equals', 'control' => $this->control, 'value' => $this->option->value ];
	}
}
