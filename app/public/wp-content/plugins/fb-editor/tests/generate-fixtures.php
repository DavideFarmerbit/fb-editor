<?php
/**
 * `composer fixtures`: regenerates tests/fixtures/styles.json from PHP.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

$file = Fb\Tests\Fixtures\StyleFixtures::FILE;

if ( ! is_dir( dirname( $file ) ) ) {
	mkdir( dirname( $file ), 0755, true );
}

file_put_contents( $file, Fb\Tests\Fixtures\StyleFixtures::json() );

fwrite( STDOUT, 'Wrote ' . realpath( $file ) . "\n" );
