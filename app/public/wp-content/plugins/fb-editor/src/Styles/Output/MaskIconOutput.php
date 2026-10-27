<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Values\ResponsiveValue;
use Fb\Values\SvgValue;

/**
 * SvgValue → the icon base CSS draws as a mask (in the text color):
 *
 *   --fb-icon: url("data:image/svg+xml,…");  --fb-icon-content: "";
 *
 * The SVG is percent-encoded whole, so nothing in it can end the url().
 */
final readonly class MaskIconOutput implements StyleOutput
{
	public function properties(): array
	{
		return [ CssProperty::IconVar, CssProperty::IconContentVar ];
	}

	public function declarations( ?ResponsiveValue $value ): DeclarationSet
	{
		if ( ! $value instanceof SvgValue ) {
			return DeclarationSet::empty();
		}

		return DeclarationSet::empty()
			->with( CssProperty::IconVar, 'url("data:image/svg+xml,' . rawurlencode( $value->markup ) . '")' )
			->with( CssProperty::IconContentVar, '""' );
	}

	/**
	 * @return array{kind: 'mask-icon'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'mask-icon' ];
	}
}
