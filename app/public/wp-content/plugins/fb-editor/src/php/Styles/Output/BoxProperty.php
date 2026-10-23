<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Values\Side;

/**
 * Four-sided CSS properties a BoxValue can drive.
 */
enum BoxProperty: string
{
	case Padding = 'padding';
	case Margin  = 'margin';

	public function longhand( Side $side ): CssProperty
	{
		return match ( $this ) {
			self::Padding => CssProperty::padding( $side ),
			self::Margin  => CssProperty::margin( $side ),
		};
	}
}
