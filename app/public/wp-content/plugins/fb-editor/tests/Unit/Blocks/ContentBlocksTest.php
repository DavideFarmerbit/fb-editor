<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\Library\Icon\Icon;
use Fb\Blocks\Library\Separator\Separator;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Tests\Fixtures\TestRendering;
use PHPUnit\Framework\TestCase;

/**
 * Icon and Separator: content values in, clean markup out.
 */
final class ContentBlocksTest extends TestCase
{
	private const string SVG = '<svg viewBox="0 0 24 24"><path d="M5 12h14"/></svg>';

	private TestRendering $rendering;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		$this->rendering = new TestRendering( new BreakpointConfig() );
	}

	protected function tearDown(): void
	{
		remove_all_filters( 'fb_breakpoints' );
	}

	public function testIconWithoutSvgRendersNothing(): void
	{
		self::assertSame( '', $this->rendering->render( new Icon(), [], '' ) );
	}

	public function testDecorativeIcon(): void
	{
		self::assertSame(
			'<span class="fb-icon" aria-hidden="true">' . self::SVG . '</span>',
			$this->rendering->render( new Icon(), [ 'fb' => [ 'base' => [ 'svg' => self::SVG . '<!-- x -->' ] ] ], '' )
		);
	}

	public function testLabelledLinkIcon(): void
	{
		$html = $this->rendering->render(
			new Icon(),
			[
				'fb' => [
					'base' => [
						'svg'   => self::SVG,
						'label' => 'Open <b>menu</b>',
						'link'  => [ 'url' => 'https://example.test/a?b=1&c=2', 'newTab' => true, 'nofollow' => true ],
					],
				],
			],
			''
		);

		self::assertSame(
			'<a class="fb-icon" href="https://example.test/a?b=1&#038;c=2" target="_blank" rel="noopener nofollow" aria-label="Open menu">' . self::SVG . '</a>',
			$html
		);
	}

	public function testUnsafeLinksAreDropped(): void
	{
		$html = $this->rendering->render( new Icon(), [ 'fb' => [ 'base' => [ 'svg' => self::SVG, 'link' => [ 'url' => 'javascript:alert(1)' ] ] ] ], '' );

		self::assertStringStartsWith( '<span class="fb-icon"', $html );
	}

	public function testSeparatorWithAndWithoutIcon(): void
	{
		self::assertSame(
			'<div class="fb-separator" role="separator"><span class="fb-separator__line"></span></div>',
			$this->rendering->render( new Separator(), [], '' )
		);
		self::assertSame(
			'<div class="fb-separator" role="separator"><span class="fb-separator__line"></span>'
			. '<span class="fb-separator__icon" aria-hidden="true">' . self::SVG . '</span>'
			. '<span class="fb-separator__line"></span></div>',
			$this->rendering->render( new Separator(), [ 'fb' => [ 'base' => [ 'svg' => self::SVG ] ] ], '' )
		);
	}
}
