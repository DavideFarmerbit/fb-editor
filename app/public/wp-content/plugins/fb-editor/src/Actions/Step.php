<?php

declare(strict_types=1);

namespace Fb\Actions;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * One trigger, condition or effect of a rule, with its parameter values:
 *
 *   {"type": "toggle", "params": {"target": {"block": "a7k2b9"}}}
 */
final readonly class Step implements \JsonSerializable
{
	public function __construct(
		public ActionType $type,
		public ParamValues $params,
	) {
	}

	/** Null for an unknown type, or a required parameter missing. */
	public static function parse( JsonValue $json, ActionKind $kind, ActionCatalog $catalog ): ?self
	{
		if ( ! $json->isObject() ) {
			return null;
		}

		try {
			$type = $catalog->find( $kind, $json->get( 'type' )->string() );
		} catch ( InvalidJson ) {
			return null;
		}
		if ( null === $type ) {
			return null;
		}

		$params = ParamValues::parse( $type->params(), $json->has( 'params' ) ? $json->get( 'params' ) : null );

		return null === $params ? null : new self( $type, $params );
	}

	/**
	 * @return array{type: string, params: ParamValues}
	 */
	public function jsonSerialize(): array
	{
		return [ 'type' => $this->type->name(), 'params' => $this->params ];
	}
}
