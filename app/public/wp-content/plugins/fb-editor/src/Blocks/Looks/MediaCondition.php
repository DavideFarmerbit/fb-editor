<?php

declare(strict_types=1);

namespace Fb\Blocks\Looks;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Conditions a look can require of the device or the visitor's settings,
 * on top of the breakpoint: a look for dark mode, for touch screens…
 *
 * Case order is cascade order among looks with the same states.
 */
#[ExportToTypeScript]
enum MediaCondition: string
{
	case CanHover      = 'can-hover';
	case Touch         = 'touch';
	case Portrait      = 'portrait';
	case Landscape     = 'landscape';
	case Dark          = 'dark';
	case ReducedMotion = 'reduced-motion';

	public function label(): string
	{
		return match ( $this ) {
			self::CanHover      => __( 'Can hover', 'fb-editor' ),
			self::Touch         => __( 'Touch', 'fb-editor' ),
			self::Portrait      => __( 'Portrait', 'fb-editor' ),
			self::Landscape     => __( 'Landscape', 'fb-editor' ),
			self::Dark          => __( 'Dark mode', 'fb-editor' ),
			self::ReducedMotion => __( 'Reduced motion', 'fb-editor' ),
		};
	}

	/** A media query of its own: combined with others (and breakpoints) by "and". */
	public function mediaQuery(): string
	{
		return match ( $this ) {
			self::CanHover      => '(hover: hover)',
			self::Touch         => '(hover: none) and (pointer: coarse)',
			self::Portrait      => '(orientation: portrait)',
			self::Landscape     => '(orientation: landscape)',
			self::Dark          => '(prefers-color-scheme: dark)',
			self::ReducedMotion => '(prefers-reduced-motion: reduce)',
		};
	}

	/**
	 * In cascade (case) order, without duplicates.
	 *
	 * @param list<self> $conditions
	 * @return list<self>
	 */
	public static function ordered( array $conditions ): array
	{
		return array_values( array_filter( self::cases(), static fn ( self $condition ): bool => in_array( $condition, $conditions, true ) ) );
	}
}
