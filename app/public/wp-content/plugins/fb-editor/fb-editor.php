<?php
/**
 * Plugin Name:       FB Editor
 * Description:       Breakpoint-aware block controls and editor tooling.
 * Version:           0.1.0
 * Requires at least: 7.1
 * Requires PHP:      8.4
 * Author:            Farmerbit
 * Text Domain:       fb-editor
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// PSR-4 for `Fb\` → src/. Composer is only needed for dev tooling.
spl_autoload_register(
	static function ( string $class ): void {
		if ( ! str_starts_with( $class, 'Fb\\' ) ) {
			return;
		}

		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class, 3 ) ) . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

( new Fb\Plugin( Fb\PluginPaths::fromPluginFile( __FILE__ ) ) )->boot();
