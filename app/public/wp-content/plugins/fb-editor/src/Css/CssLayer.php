<?php

declare(strict_types=1);

namespace Fb\Css;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Cascade layers, lowest priority first.
 *
 * Any unlayered CSS (a theme or site stylesheet) beats all of these
 * regardless of specificity, so overrides never need !important.
 */
#[ExportToTypeScript]
enum CssLayer: string
{
	/** Core CSS we keep but demote below everything else. */
	case Wp = 'wp';
	/** Design tokens (preset custom properties). */
	case Tokens = 'fb.tokens';
	/** Functional base CSS of managed core blocks (images never overflow…). */
	case Base = 'fb.base';
	/** Per-block-type defaults. */
	case Defaults = 'fb.defaults';
	/** Named looks of a block type, used by blocks that pick them. */
	case Variants = 'fb.variants';
	/** Per-block-instance values. */
	case Instances = 'fb.instances';
	/**
	 * State styles (hover…) sit above every base layer: any state beats any
	 * base look, e.g. a default hover background beats a block's own
	 * background while hovered.
	 */
	case StateDefaults = 'fb.states.defaults';
	case StateVariants = 'fb.states.variants';
	case StateInstances = 'fb.states.instances';

	/**
	 * Establishes the order. Must precede (or accompany) the first fb layer.
	 */
	public static function orderStatement(): string
	{
		return '@layer ' . implode( ',', array_map( static fn ( self $layer ): string => $layer->value, self::cases() ) ) . ';';
	}

	/** Where this base layer's state styles go. */
	public function states(): self
	{
		return match ( $this ) {
			self::Defaults  => self::StateDefaults,
			self::Variants  => self::StateVariants,
			self::Instances => self::StateInstances,
			default         => throw new \LogicException( "No state layer for {$this->value}." ),
		};
	}

	public function wrap( string $css ): string
	{
		return "@layer {$this->value}{{$css}}";
	}
}
