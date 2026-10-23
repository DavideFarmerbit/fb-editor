<?php

declare(strict_types=1);

namespace Fb\Tests\Unit;

use Fb\Tests\Fixtures\StyleFixtures;
use PHPUnit\Framework\TestCase;

/**
 * The JS suite checks its compiler against tests/fixtures/styles.json.
 * This makes sure that file still reflects what PHP produces.
 */
final class StyleFixturesTest extends TestCase
{
	public function testFixturesAreUpToDate(): void
	{
		self::assertFileExists( StyleFixtures::FILE, 'Run `composer fixtures`.' );
		self::assertSame(
			StyleFixtures::json(),
			(string) file_get_contents( StyleFixtures::FILE ),
			'PHP output changed: run `composer fixtures`, then `npm test` to check the JS side.'
		);
	}
}
