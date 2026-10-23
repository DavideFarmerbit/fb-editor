<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\KeywordValue;

/**
 * Pick one of a fixed set of options, defined by a LabeledOption enum.
 */
final readonly class SelectInput implements Input
{
	/**
	 * @param class-string<LabeledOption> $options
	 */
	public function __construct(
		public string $options,
	) {
	}

	/**
	 * @return list<LabeledOption>
	 */
	public function cases(): array
	{
		return array_values( $this->options::cases() );
	}

	public function parse( JsonValue $json ): KeywordValue
	{
		$value = $json->string();

		if ( null === $this->options::tryFrom( $value ) ) {
			throw new InvalidJson( "{$json->path}: \"{$value}\" is not an option" );
		}

		return new KeywordValue( $value );
	}

	/**
	 * @return array{kind: 'select', options: list<array{value: int|string, label: string}>}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'    => 'select',
			'options' => array_map(
				static fn ( LabeledOption $option ): array => [ 'value' => $option->value, 'label' => $option->label() ],
				$this->cases()
			),
		];
	}
}
