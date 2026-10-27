<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Svg;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\SvgValue;

/**
 * Pasted SVG code, stored sanitized (see SvgSanitizer).
 */
final readonly class SvgInput implements Input
{
	public function parse( JsonValue $json ): SvgValue
	{
		return SvgSanitizer::sanitize( $json->string() ) ?? throw new InvalidJson( "{$json->path}: not a usable SVG" );
	}

	/**
	 * @return array{kind: 'svg'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'svg' ];
	}
}
