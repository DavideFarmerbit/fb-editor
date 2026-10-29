<?php

declare(strict_types=1);

namespace Fb\Actions\Library\ScrollTo;

use Fb\Actions\Catalog\Effect;
use Fb\Actions\Catalog\Param;
use Fb\Blocks\Definition\Input\Length\LengthInput;

/**
 * Scrolls the page to the target, smoothly unless the visitor prefers
 * reduced motion; the offset leaves room for a fixed header.
 */
final class ScrollTo extends Effect
{
	public const string OFFSET = 'offset';

	public function name(): string
	{
		return 'scroll-to';
	}

	public function label(): string
	{
		return __( 'Scroll to', 'fb-editor' );
	}

	public function params(): array
	{
		return [ Param::target(), new Param( self::OFFSET, __( 'Offset', 'fb-editor' ), new LengthInput( allowNegative: true ) ) ];
	}
}
