<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Target;

use Fb\Actions\BlockAnchor;
use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * Picks the block an action works on: itself, or another block picked in
 * the canvas (stored by its anchor).
 */
final readonly class TargetInput implements Input
{
	public function parse( JsonValue $json ): TargetValue
	{
		if ( ! $json->isObject() ) {
			if ( 'self' !== $json->string() ) {
				throw new InvalidJson( "{$json->path}: expected \"self\" or a block" );
			}

			return new TargetValue();
		}

		$anchor = BlockAnchor::tryFrom( $json->get( 'block' )->string() );
		if ( null === $anchor ) {
			throw new InvalidJson( "{$json->path}.block: not a block anchor" );
		}

		return new TargetValue( $anchor );
	}

	/**
	 * @return array{kind: 'target'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'target' ];
	}
}
