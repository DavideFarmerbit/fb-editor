<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Select;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\KeywordValue;

/**
 * Pick one of a fixed set of options, defined by a LabeledOption enum.
 *
 * `fallback` is what render() and the editor use while nothing is chosen
 * (e.g. the HTML tag). It is not a style: an unset select writes no CSS.
 */
final readonly class SelectInput implements Input
{
	/**
	 * @param class-string<LabeledOption> $options
	 */
	public function __construct(
		public string $options,
		public ?LabeledOption $fallback = null,
	) {
		if ( null !== $fallback && ! $fallback instanceof $options ) {
			throw new \InvalidArgumentException( 'The fallback must be one of the options.' );
		}
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
	 * @return array{kind: 'select', options: list<array{value: int|string, label: string}>, fallback: int|string|null}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'     => 'select',
			'options'  => array_map(
				static fn ( LabeledOption $option ): array => [ 'value' => $option->value, 'label' => $option->label() ],
				$this->cases()
			),
			'fallback' => $this->fallback?->value,
		];
	}
}
