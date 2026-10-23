<?php
/**
 * Loads WordPress from the Local site (some value objects use core helpers,
 * e.g. preset variable naming). The site must be running in Local.
 */

declare(strict_types=1);

$_SERVER['HTTP_HOST']   ??= 'guttemberg.local';
$_SERVER['REQUEST_URI'] ??= '/';

define( 'WP_USE_THEMES', false );

require dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! class_exists( Fb\Plugin::class ) ) {
	fwrite( STDERR, "fb-editor is not active on the site.\n" );
	exit( 1 );
}
