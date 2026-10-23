<?php

declare(strict_types=1);

namespace Fb\Breakpoints;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * Loads the breakpoint list.
 *
 * Source, in order of precedence:
 *   1. `fb_breakpoints` filter (receives and must return a BreakpointList)
 *   2. `fb.json` in the active (child) theme root
 *   3. Built-in fallback
 *
 * A broken fb.json never breaks the site: it is reported with
 * _doing_it_wrong() and the fallback is used.
 */
final class BreakpointConfig
{
	public const string FILE = 'fb.json';

	private ?BreakpointList $list = null;

	public function breakpoints(): BreakpointList
	{
		return $this->list ??= $this->load();
	}

	public static function fallback(): BreakpointList
	{
		return new BreakpointList(
			new Breakpoint( new BreakpointId( 'desktop' ), 'Desktop', BreakpointIcon::Desktop ),
			new Breakpoint( new BreakpointId( 'tablet' ), 'Tablet', BreakpointIcon::Tablet, 1024 ),
			new Breakpoint( new BreakpointId( 'mobile' ), 'Mobile', BreakpointIcon::Mobile, 767 ),
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
		$file = get_theme_file_path( self::FILE );

		if ( ! is_readable( $file ) ) {
			return null;
		}

		try {
			return self::parse( JsonValue::parse( (string) file_get_contents( $file ), self::FILE ) );
		} catch ( InvalidJson | \InvalidArgumentException $e ) {
			_doing_it_wrong( __METHOD__, esc_html( $e->getMessage() ) . ' Falling back to default breakpoints.', '0.1.0' );
			return null;
		}
	}

	/**
	 * @throws InvalidJson|\InvalidArgumentException
	 */
	public static function parse( JsonValue $root ): BreakpointList
	{
		$base   = null;
		$others = [];

		foreach ( $root->get( 'breakpoints' )->list() as $item ) {
			$icon       = $item->optionalString( 'icon' );
			$breakpoint = new Breakpoint(
				id: new BreakpointId( $item->get( 'id' )->string() ),
				label: $item->get( 'label' )->string(),
				icon: null === $icon ? null : BreakpointIcon::tryFrom( $icon ),
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
			throw new InvalidJson( "{$root->path}.breakpoints: one breakpoint must omit maxWidth (the base)." );
		}

		return new BreakpointList( $base, ...$others );
	}
}
