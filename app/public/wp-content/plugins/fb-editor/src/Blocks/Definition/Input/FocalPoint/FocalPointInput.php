<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\FocalPoint;

use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\FocalPoint;

/**
 * A focal point, picked on the image of another control of the same block
 * (`media`, a MediaInput): the editor shows that image to click on.
 */
final readonly class FocalPointInput implements Input
{
	public function __construct(
		public ControlId $media,
	) {
	}

	public function parse( JsonValue $json ): FocalPoint
	{
		try {
			return new FocalPoint( $json->get( 'x' )->float(), $json->get( 'y' )->float() );
		} catch ( \InvalidArgumentException $e ) {
			throw new InvalidJson( "{$json->path}: {$e->getMessage()}", 0, $e );
		}
	}

	/**
	 * @return array{kind: 'focal-point', media: ControlId}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'focal-point', 'media' => $this->media ];
	}
}
