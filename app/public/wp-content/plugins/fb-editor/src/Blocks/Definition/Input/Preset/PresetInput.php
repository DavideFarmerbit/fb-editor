<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Preset;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Presets\PresetKind;
use Fb\Values\PresetReference;

/**
 * Pick one of the theme's presets of a kind (e.g. a font family). Options
 * are whatever the theme defines, so they live in theme.json, not an enum.
 */
final readonly class PresetInput implements Input
{
	public function __construct(
		public PresetKind $kind,
	) {
	}

	public function parse( JsonValue $json ): PresetReference
	{
		try {
			$reference = new PresetReference(
				PresetKind::tryFrom( $json->get( 'preset' )->string() ) ?? throw new InvalidJson( "{$json->path}.preset: unknown preset kind" ),
				$json->get( 'slug' )->string(),
			);
		} catch ( \InvalidArgumentException $e ) {
			throw new InvalidJson( "{$json->path}: {$e->getMessage()}", 0, $e );
		}

		if ( 'preset' !== $json->get( 'kind' )->string() || $reference->preset !== $this->kind ) {
			throw new InvalidJson( "{$json->path}: expected a {$this->kind->value} preset" );
		}

		return $reference;
	}

	/**
	 * @return array{kind: 'preset', preset: string}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'preset', 'preset' => $this->kind->value ];
	}
}
