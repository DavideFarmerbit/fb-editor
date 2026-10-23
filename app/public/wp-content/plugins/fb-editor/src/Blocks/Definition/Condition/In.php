<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Condition;

use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\Input\LabeledOption;
use Fb\Blocks\Values\ResolvedValues;
use Fb\Values\KeywordValue;

/**
 * True when a select control's resolved value is one of the given options.
 */
final readonly class In implements Condition
{
	/** @var non-empty-list<LabeledOption> */
	public array $options;

	public function __construct(
		public ControlId $control,
		LabeledOption $option,
		LabeledOption ...$more,
	) {
		$this->options = [ $option, ...array_values( $more ) ];
	}

	public function evaluate( ResolvedValues $values ): bool
	{
		$value = $values->get( $this->control );

		if ( ! $value instanceof KeywordValue ) {
			return false;
		}

		foreach ( $this->options as $option ) {
			if ( $value->keyword === (string) $option->value ) {
				return true;
			}
		}

		return false;
	}

	public function references(): array
	{
		return [ $this->control ];
	}

	/**
	 * @return array{kind: 'in', control: ControlId, values: list<int|string>}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'    => 'in',
			'control' => $this->control,
			'values'  => array_map( static fn ( LabeledOption $option ): int|string => $option->value, $this->options ),
		];
	}
}
