<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Presets\PresetKind;

/**
 * JSON boundary for Length.
 */
final class Lengths
{
	/**
	 * @throws InvalidJson
	 */
	public static function fromJson( JsonValue $json ): Length
	{
		$kind = $json->get( 'kind' )->string();

		try {
			return match ( $kind ) {
				'preset' => new PresetLength(
					PresetKind::tryFrom( $json->get( 'preset' )->string() ) ?? throw new InvalidJson( "{$json->path}.preset: unknown preset kind" ),
					$json->get( 'slug' )->string(),
				),
				'custom' => new CustomLength(
					$json->get( 'value' )->float(),
					Unit::tryFrom( $json->get( 'unit' )->string() ) ?? throw new InvalidJson( "{$json->path}.unit: unknown unit" ),
				),
				'auto'   => new AutoLength(),
				default  => throw new InvalidJson( "{$json->path}.kind: unknown length kind \"{$kind}\"" ),
			};
		} catch ( \InvalidArgumentException $e ) {
			throw new InvalidJson( "{$json->path}: {$e->getMessage()}", 0, $e );
		}
	}
}
