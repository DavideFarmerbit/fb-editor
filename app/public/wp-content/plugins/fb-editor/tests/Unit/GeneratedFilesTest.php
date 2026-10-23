<?php

declare(strict_types=1);

namespace Fb\Tests\Unit;

use Fb\Tests\Fixtures\StyleFixtures;
use Fb\Tests\Tools\TypeScriptExporter;
use PHPUnit\Framework\TestCase;

/**
 * Everything derived from PHP must match PHP. If this fails, run
 * `composer generate`, then `npm test` to check the JS side.
 */
final class GeneratedFilesTest extends TestCase
{
	public function testTypeScriptExportsAreUpToDate(): void
	{
		$exporter = new TypeScriptExporter( dirname( __DIR__, 2 ) . '/src' );

		foreach ( $exporter->expected() as $path => $content ) {
			self::assertFileExists( $path, 'Run `composer generate`.' );
			self::assertSame( $content, (string) file_get_contents( $path ), basename( $path ) . ' is stale: run `composer generate`.' );
		}
		self::assertSame( [], $exporter->orphans(), 'Generated files without a PHP source: run `composer generate`.' );
	}

	public function testStyleFixturesAreUpToDate(): void
	{
		self::assertFileExists( StyleFixtures::FILE, 'Run `composer generate`.' );
		self::assertSame( StyleFixtures::json(), (string) file_get_contents( StyleFixtures::FILE ), 'styles.json is stale: run `composer generate`.' );
	}
}
