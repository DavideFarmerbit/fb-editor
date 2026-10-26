<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\CoreBlocks;

use Fb\CoreBlocks\CoreBlockStylesheets;
use Fb\Output\CanvasStylePolicy;
use Fb\Output\FrontendStylePolicy;
use Fb\Output\StyleAction;
use PHPUnit\Framework\TestCase;

/**
 * The canvas loads the same core block CSS as the frontend: wp-admin's
 * bundled library is split, managed blocks (button padding, radius…) out.
 */
final class CoreBlockStylesheetsTest extends TestCase
{
	public function testKeepsUnmanagedBlocksOnly(): void
	{
		$urls = implode( "\n", ( new CoreBlockStylesheets() )->unmanagedUrls() );

		self::assertStringContainsString( '/blocks/group/style', $urls );
		self::assertStringContainsString( '/blocks/navigation/style', $urls );
		foreach ( [ 'button', 'buttons', 'paragraph', 'heading', 'list', 'image' ] as $managed ) {
			self::assertStringNotContainsString( "/blocks/{$managed}/style", $urls );
		}
	}

	public function testCanvasUnbundlesTheLibraryTheFrontendDrops(): void
	{
		self::assertSame( StyleAction::Unbundle, ( new CanvasStylePolicy() )->decide( 'wp-block-library' ) );
		self::assertSame( StyleAction::Remove, ( new FrontendStylePolicy() )->decide( 'wp-block-library' ) );
		self::assertSame( StyleAction::Remove, ( new CanvasStylePolicy() )->decide( 'wp-block-button' ) );
		self::assertSame( StyleAction::Keep, ( new CanvasStylePolicy() )->decide( 'wp-block-button-editor' ) );
	}
}
