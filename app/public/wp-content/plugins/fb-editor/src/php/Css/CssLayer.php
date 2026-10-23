<?php

declare(strict_types=1);

namespace Fb\Css;

/**
 * Cascade layers, lowest priority first.
 *
 * Any unlayered CSS (a theme or site stylesheet) beats all of these
 * regardless of specificity, so overrides never need !important.
 */
enum CssLayer: string
{
	/** Core CSS we keep but demote below everything else. */
	case Wp = 'wp';
	/** Design tokens (preset custom properties). */
	case Tokens = 'fb.tokens';
	/** Per-block-type defaults. */
	case Defaults = 'fb.defaults';
	/** Per-block-instance values. */
	case Instances = 'fb.instances';

	/**
	 * Establishes the order. Must precede (or accompany) the first fb layer.
	 */
	public static function orderStatement(): string
	{
		return '@layer ' . implode( ',', array_map( static fn ( self $layer ): string => $layer->value, self::cases() ) ) . ';';
	}

	public function wrap( string $css ): string
	{
		return "@layer {$this->value}{{$css}}";
	}
}
