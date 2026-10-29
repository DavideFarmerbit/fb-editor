<?php

declare(strict_types=1);

namespace Fb\Actions\Rules;

use Fb\Actions\Catalog\Param;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\ResponsiveValue;

/**
 * The values of one step's parameters, each parsed (sanitized) through its
 * input. Unknown parameters and invalid values are dropped.
 */
final readonly class ParamValues implements \JsonSerializable
{
	/**
	 * @param array<string, ResponsiveValue> $values Keyed by parameter id, in parameter order. Private storage.
	 */
	private function __construct(
		private array $values,
	) {
	}

	/**
	 * Null when a required parameter is missing or invalid.
	 *
	 * @param list<Param> $params
	 */
	public static function parse( array $params, ?JsonValue $json ): ?self
	{
		$values = [];
		foreach ( $params as $param ) {
			$value = null;
			if ( null !== $json && $json->isObject() && $json->has( $param->id ) ) {
				try {
					$value = $param->input->parse( $json->get( $param->id ) );
				} catch ( InvalidJson ) {
					$value = null;
				}
			}
			if ( null === $value ) {
				if ( $param->required ) {
					return null;
				}
				continue;
			}
			$values[ $param->id ] = $value;
		}

		return new self( $values );
	}

	public function get( string $id ): ?ResponsiveValue
	{
		return $this->values[ $id ] ?? null;
	}

	/**
	 * @return array<string, ResponsiveValue>|\stdClass
	 */
	public function jsonSerialize(): array|\stdClass
	{
		return [] === $this->values ? new \stdClass() : $this->values;
	}
}
