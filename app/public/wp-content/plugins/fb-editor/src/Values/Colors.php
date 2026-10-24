<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Presets\PresetKind;

/**
 * JSON boundary for Color.
 */
final class Colors
{
	/**
	 * @throws InvalidJson
	 */
	public static function fromJson( JsonValue $json ): Color
	{
		$kind = $json->get( 'kind' )->string();

		try {
			return match ( $kind ) {
				'preset' => new PresetReference(
					PresetKind::tryFrom( $json->get( 'preset' )->string() ) ?? throw new InvalidJson( "{$json->path}.preset: unknown preset kind" ),
					$json->get( 'slug' )->string(),
				),
				'custom' => new CustomColor( $json->get( 'value' )->string() ),
				default  => throw new InvalidJson( "{$json->path}.kind: unknown color kind \"{$kind}\"" ),
			};
		} catch ( \InvalidArgumentException $e ) {
			throw new InvalidJson( "{$json->path}: {$e->getMessage()}", 0, $e );
		}
	}
}
