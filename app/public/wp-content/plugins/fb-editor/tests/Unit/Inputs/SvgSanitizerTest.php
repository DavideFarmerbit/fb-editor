<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Inputs;

use Fb\Blocks\Definition\Input\Svg\SvgSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pasted SVG is printed as is on the frontend: anything that could run,
 * load or leak must be gone.
 */
final class SvgSanitizerTest extends TestCase
{
	public function testKeepsAPlainIcon(): void
	{
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><circle cx="12" cy="12" r="3"/></svg>';

		self::assertSame( $icon, SvgSanitizer::sanitize( $icon )?->markup );
	}

	/**
	 * @return iterable<string, array{string, list<string>}>
	 */
	public static function hostile(): iterable
	{
		yield 'script element' => [ '<svg><script>alert(1)</script><path d="M0 0"/></svg>', [ 'script', 'alert' ] ];
		yield 'event handler' => [ '<svg onload="alert(1)"><path onclick="x()" d="M0 0"/></svg>', [ 'onload', 'onclick' ] ];
		yield 'javascript link' => [ '<svg><a href="javascript:alert(1)"><path d="M0 0"/></a></svg>', [ '<a', 'javascript' ] ];
		yield 'external use' => [ '<svg><use href="https://evil.test/x.svg#a"/><use xlink:href="data:x"/></svg>', [ 'evil', 'data:' ] ];
		yield 'foreignObject' => [ '<svg><foreignObject><div>hi</div></foreignObject></svg>', [ 'foreignObject', 'div' ] ];
		yield 'style and url loads' => [ '<svg><path style="fill:url(https://evil.test)" fill="url(https://evil.test)" d="M0 0"/></svg>', [ 'style', 'evil' ] ];
		yield 'animation setting href' => [ '<svg><set attributeName="href" to="javascript:alert(1)"/><animate/></svg>', [ 'set', 'animate', 'javascript' ] ];
		yield 'class and image' => [ '<svg class="x"><image href="x.png"/></svg>', [ 'class', 'image' ] ];
	}

	/**
	 * @param list<string> $gone
	 */
	#[DataProvider( 'hostile' )]
	public function testStripsWhatCouldRunOrLoad( string $raw, array $gone ): void
	{
		$markup = SvgSanitizer::sanitize( $raw )->markup ?? '';

		self::assertStringStartsWith( '<svg', $markup );
		foreach ( $gone as $needle ) {
			self::assertStringNotContainsString( $needle, $markup );
		}
	}

	public function testKeepsLocalReferences(): void
	{
		$markup = SvgSanitizer::sanitize( '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"><stop offset="0" stop-color="red"/></linearGradient></defs><rect fill="url(#g)" width="1" height="1"/><use href="#g"/></svg>' )->markup ?? '';

		self::assertStringContainsString( 'fill="url(#g)"', $markup );
		self::assertStringContainsString( 'href="#g"', $markup );
	}

	public function testRejectsWhatIsNotAnSvg(): void
	{
		self::assertNull( SvgSanitizer::sanitize( '' ) );
		self::assertNull( SvgSanitizer::sanitize( '<div>no</div>' ) );
		self::assertNull( SvgSanitizer::sanitize( '<svg><path' ) );
		self::assertNull( SvgSanitizer::sanitize( '<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg>&x;</svg>' ) );
		self::assertNull( SvgSanitizer::sanitize( '<svg>' . str_repeat( ' ', SvgSanitizer::MAX_LENGTH ) . '</svg>' ) );
	}
}
