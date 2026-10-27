<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\GridColumns;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\CustomLength;
use Fb\Values\GridColumnCount;
use Fb\Values\GridColumnFit;
use Fb\Values\GridColumns;
use Fb\Values\Lengths;

/**
 * A grid's columns: a count (1…maxCount), or "as many as fit" with a
 * minimum column width (a positive custom length).
 */
final readonly class GridColumnsInput implements Input
{
	/** What the editor switches to when picking each mode. */
	#[ExportToTypeScript]
	public const int DEFAULT_COUNT = 2;

	#[ExportToTypeScript]
	public const float DEFAULT_FIT_MIN_PX = 240.0;

	public function __construct(
		public int $maxCount = 12,
	) {
		if ( $maxCount < 1 ) {
			throw new \InvalidArgumentException( 'GridColumnsInput: maxCount must be at least 1.' );
		}
	}

	public function parse( JsonValue $json ): GridColumns
	{
		$kind = $json->get( 'kind' )->string();

		try {
			$columns = match ( $kind ) {
				'count' => new GridColumnCount( $json->get( 'count' )->int() ),
				'fit'   => new GridColumnFit( self::customLength( $json->get( 'min' ) ) ),
				default => throw new InvalidJson( "{$json->path}.kind: unknown grid columns kind \"{$kind}\"" ),
			};
		} catch ( \InvalidArgumentException $e ) {
			throw new InvalidJson( "{$json->path}: {$e->getMessage()}", 0, $e );
		}

		if ( $columns instanceof GridColumnCount && $columns->count > $this->maxCount ) {
			throw new InvalidJson( "{$json->path}: more than {$this->maxCount} columns" );
		}

		return $columns;
	}

	/**
	 * @throws InvalidJson
	 */
	private static function customLength( JsonValue $json ): CustomLength
	{
		$length = Lengths::fromJson( $json );

		return $length instanceof CustomLength ? $length : throw new InvalidJson( "{$json->path}: expected a custom length" );
	}

	/**
	 * @return array{kind: 'grid-columns', maxCount: int}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'grid-columns', 'maxCount' => $this->maxCount ];
	}
}
