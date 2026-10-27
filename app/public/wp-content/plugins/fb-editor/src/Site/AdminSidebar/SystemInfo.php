<?php

declare(strict_types=1);

namespace Fb\Site\AdminSidebar;

/**
 * The server and site facts the sidebar lists.
 *
 * Media and database sizes need a full walk of the uploads folder and a
 * schema query: measured once an hour, not on every page view.
 */
final readonly class SystemInfo
{
	private const string SIZES_CACHE = 'fb_admin_sidebar_sizes';

	/**
	 * @return list<InfoItem>
	 */
	public function items(): array
	{
		global $wpdb;

		$debug          = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$discouraged    = in_array( get_option( 'blog_public' ), [ '0', 0 ], true );
		[ $media, $db ] = $this->sizes();
		$free           = disk_free_space( ABSPATH );

		return [
			new InfoItem( __( 'Debug mode', 'fb-editor' ), $debug ? __( 'On', 'fb-editor' ) : __( 'Off', 'fb-editor' ), $debug ),
			new InfoItem( __( 'Search engines', 'fb-editor' ), $discouraged ? __( 'Discouraged', 'fb-editor' ) : __( 'Allowed', 'fb-editor' ), $discouraged ),
			new InfoItem( 'PHP', PHP_VERSION ),
			new InfoItem( 'WordPress', get_bloginfo( 'version' ) ),
			new InfoItem( __( 'Database', 'fb-editor' ), $wpdb instanceof \wpdb ? (string) $wpdb->db_server_info() : '—' ),
			new InfoItem( __( 'Memory used', 'fb-editor' ), self::bytes( memory_get_usage() ) ),
			new InfoItem( __( 'Memory limit', 'fb-editor' ), (string) ini_get( 'memory_limit' ) ),
			new InfoItem( __( 'Max execution', 'fb-editor' ), ini_get( 'max_execution_time' ) . 's' ),
			new InfoItem( __( 'Upload max', 'fb-editor' ), (string) ini_get( 'upload_max_filesize' ) ),
			new InfoItem( __( 'Post max size', 'fb-editor' ), (string) ini_get( 'post_max_size' ) ),
			new InfoItem( __( 'Media size', 'fb-editor' ), $media ),
			new InfoItem( __( 'Database size', 'fb-editor' ), $db ),
			new InfoItem( __( 'Free disk space', 'fb-editor' ), false === $free ? '—' : self::bytes( (int) $free ) ),
		];
	}

	/** Loaded PHP extensions, alphabetically. */
	public function extensions(): string
	{
		$extensions = get_loaded_extensions();
		natcasesort( $extensions );

		return implode( ', ', $extensions );
	}

	/**
	 * Media and database sizes, formatted.
	 *
	 * @return array{string, string}
	 */
	private function sizes(): array
	{
		$cached = get_transient( self::SIZES_CACHE );
		if ( is_array( $cached ) && is_string( $cached[0] ?? null ) && is_string( $cached[1] ?? null ) ) {
			return [ $cached[0], $cached[1] ];
		}

		$uploads = wp_upload_dir( null, false );
		$sizes   = [
			self::bytes( self::directorySize( $uploads['basedir'] ) ),
			$this->databaseSize(),
		];
		set_transient( self::SIZES_CACHE, $sizes, HOUR_IN_SECONDS );

		return $sizes;
	}

	private static function directorySize( string $path ): int
	{
		if ( ! is_dir( $path ) ) {
			return 0;
		}

		$total = 0;
		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			if ( $file instanceof \SplFileInfo && $file->isFile() ) {
				$total += (int) $file->getSize();
			}
		}

		return $total;
	}

	private function databaseSize(): string
	{
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return '—';
		}

		// The connection's own database: no name to pass in.
		$size = $wpdb->get_var( 'SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = DATABASE()' );

		return is_numeric( $size ) ? self::bytes( (int) $size ) : '—';
	}

	/** e.g. "12.3 MB"; "—" when WordPress can't format it. */
	private static function bytes( int $bytes ): string
	{
		$formatted = size_format( $bytes, 1 );

		return false === $formatted ? '—' : $formatted;
	}
}
