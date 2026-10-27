<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Media;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\MediaKind;
use Fb\Values\MediaValue;

/**
 * An item from the media library, of the given kinds. Whether it still
 * exists is checked when rendering, not here (it may be deleted later).
 */
final readonly class MediaInput implements Input
{
	/** @var non-empty-list<MediaKind> */
	public array $kinds;

	public function __construct( MediaKind $kind, MediaKind ...$more )
	{
		$this->kinds = [ $kind, ...array_values( $more ) ];
	}

	public function parse( JsonValue $json ): MediaValue
	{
		$kind = MediaKind::tryFrom( $json->get( 'kind' )->string() );
		if ( null === $kind || ! in_array( $kind, $this->kinds, true ) ) {
			throw new InvalidJson( "{$json->path}.kind: media kind not allowed" );
		}

		try {
			return new MediaValue( $json->get( 'id' )->int(), $kind );
		} catch ( \InvalidArgumentException $e ) {
			throw new InvalidJson( "{$json->path}: {$e->getMessage()}", 0, $e );
		}
	}

	/**
	 * @return array{kind: 'media', kinds: non-empty-list<MediaKind>}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'media', 'kinds' => $this->kinds ];
	}
}
