<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\AspectRatio;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\AspectRatio;

/**
 * Width : height, two positive numbers.
 */
final readonly class AspectRatioInput implements Input
{
	public function parse( JsonValue $json ): AspectRatio
	{
		try {
			return new AspectRatio( $json->get( 'width' )->float(), $json->get( 'height' )->float() );
		} catch ( \InvalidArgumentException $e ) {
			throw new InvalidJson( "{$json->path}: {$e->getMessage()}", 0, $e );
		}
	}

	/**
	 * @return array{kind: 'aspect-ratio'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'aspect-ratio' ];
	}
}
