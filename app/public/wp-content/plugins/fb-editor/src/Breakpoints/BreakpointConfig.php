<?php

declare(strict_types=1);

namespace Fb\Breakpoints;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Theme\ThemeConfigFile;

/**
 * Loads the breakpoint list.
 *
 * Source, in order of precedence:
 *   1. `fb_breakpoints` filter (receives and must return a BreakpointList)
 *   2. "breakpoints" in the theme's fb.json
 *   3. Built-in fallback
 *
 * Invalid breakpoints are reported and the fallback is used.
 */
final class BreakpointConfig
{
	private ?BreakpointList $list = null;

	public function __construct(
		private readonly ThemeConfigFile $file = new ThemeConfigFile(),
	) {
	}

	public function breakpoints(): BreakpointList
	{
		return $this->list ??= $this->load();
	}

	public static function fallback(): BreakpointList
	{
		// Bootstrap's ranges, desktop-first: each id covers exactly
		// Bootstrap's range of the same name (lg = 992–1199px…).
		return new BreakpointList(
			new Breakpoint( new BreakpointId( 'xxl' ), 'XXL' ),
			new Breakpoint( new BreakpointId( 'xl' ), 'XL', 1399 ),
			new Breakpoint( new BreakpointId( 'lg' ), 'LG', 1199 ),
			new Breakpoint( new BreakpointId( 'md' ), 'MD', 991 ),
			new Breakpoint( new BreakpointId( 'sm' ), 'SM', 767 ),
			new Breakpoint( new BreakpointId( 'xs' ), 'XS', 575 ),
		);
	}

	private function load(): BreakpointList
	{
		$list = $this->fromThemeFile() ?? self::fallback();

		$filtered = apply_filters( 'fb_breakpoints', $list );

		if ( ! $filtered instanceof BreakpointList ) {
			_doing_it_wrong( 'fb_breakpoints', 'The filter must return a ' . BreakpointList::class . '.', '0.1.0' );
			return $list;
		}

		return $filtered;
	}

	private function fromThemeFile(): ?BreakpointList
	{
		$section = $this->file->section( 'breakpoints' );

		if ( null === $section ) {
			return null;
		}

		try {
			return self::parse( $section );
		} catch ( InvalidJson | \InvalidArgumentException $e ) {
			$this->file->report( $e->getMessage() . ' Falling back to default breakpoints.' );
			return null;
		}
	}

	/**
	 * @throws InvalidJson|\InvalidArgumentException
	 */
	public static function parse( JsonValue $breakpoints ): BreakpointList
	{
		$base   = null;
		$others = [];

		foreach ( $breakpoints->list() as $item ) {
			$breakpoint = new Breakpoint(
				id: new BreakpointId( $item->get( 'id' )->string() ),
				label: $item->get( 'label' )->string(),
				maxWidth: $item->optionalInt( 'maxWidth' ),
				previewWidth: $item->optionalInt( 'previewWidth' ),
			);

			if ( ! $breakpoint->isBase() ) {
				$others[] = $breakpoint;
			} elseif ( null === $base ) {
				$base = $breakpoint;
			} else {
				throw new InvalidJson( "{$item->path}: only one breakpoint may omit maxWidth (the base)." );
			}
		}

		if ( null === $base ) {
			throw new InvalidJson( "{$breakpoints->path}: one breakpoint must omit maxWidth (the base)." );
		}

		return new BreakpointList( $base, ...$others );
	}
}
