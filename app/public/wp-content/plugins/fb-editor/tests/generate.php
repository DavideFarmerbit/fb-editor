<?php
/**
 * `composer generate`: regenerates everything derived from PHP.
 * - src/**\/<Class>.generated.ts (enums/constants marked #[ExportToTypeScript])
 * - tests/Fixtures/styles.json and actions.json (PHP ↔ JS parity cases)
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

use Fb\Tests\Fixtures\ActionFixtures;
use Fb\Tests\Fixtures\StyleFixtures;
use Fb\Tests\Tools\TypeScriptExporter;

$exporter = new TypeScriptExporter( dirname( __DIR__ ) . '/src' );
$exporter->write();
fwrite( STDOUT, 'TypeScript: ' . count( $exporter->expected() ) . " generated files\n" );

file_put_contents( StyleFixtures::FILE, StyleFixtures::json() );
fwrite( STDOUT, 'Fixtures: ' . StyleFixtures::FILE . "\n" );

file_put_contents( ActionFixtures::FILE, ActionFixtures::json() );
fwrite( STDOUT, 'Fixtures: ' . ActionFixtures::FILE . "\n" );
