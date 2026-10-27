<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Breakpoints;

use Fb\Blocks\Definition\Input\Input;
use Fb\Breakpoints\BreakpointId;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\BreakpointSet;

/**
 * A pick of breakpoints, edited as the breakpoint strip itself (one toggle
 * per breakpoint). Ids the theme no longer defines are kept but ignored by
 * whoever reads the value: they come back if the breakpoint does.
 */
final readonly class BreakpointsInput implements Input
{
	public function parse( JsonValue $json ): BreakpointSet
	{
		$ids = [];
		foreach ( $json->list() as $item ) {
			try {
				$ids[] = new BreakpointId( $item->string() );
			} catch ( \InvalidArgumentException $e ) {
				throw new InvalidJson( "{$item->path}: {$e->getMessage()}" );
			}
		}

		return new BreakpointSet( ...$ids );
	}

	/**
	 * @return array{kind: 'breakpoints'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'breakpoints' ];
	}
}
