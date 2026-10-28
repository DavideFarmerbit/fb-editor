<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\BaseStyles\BaseStylesheet;
use Fb\Blocks\Library\Container\Container;
use Fb\Breakpoints\BreakpointConfig;
use Fb\CoreBlocks\Library\Button\Button;
use Fb\Styles\Output\MaskIconOutput;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Tests\Fixtures\TestRendering;
use Fb\Tests\Fixtures\TestLooks;
use Fb\Values\SvgValue;
use PHPUnit\Framework\TestCase;

/**
 * Button icon (a mask in the text color) and Container background media
 * and overlay.
 */
final class CoverAndIconTest extends TestCase
{
	private TestRendering $rendering;
	private ?int $attachment = null;

	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		$this->rendering = new TestRendering( new BreakpointConfig() );
	}

	protected function tearDown(): void
	{
		if ( null !== $this->attachment ) {
			wp_delete_attachment( $this->attachment, true );
		}
		remove_all_filters( 'fb_breakpoints' );
	}

	public function testIconIsAPercentEncodedMask(): void
	{
		$declarations = ( new MaskIconOutput() )->declarations( new SvgValue( '<svg viewBox="0 0 24 24"><path d="M5 12h14"/></svg>' ) );

		self::assertSame(
			'--fb-icon:url("data:image/svg+xml,%3Csvg%20viewBox%3D%220%200%2024%2024%22%3E%3Cpath%20d%3D%22M5%2012h14%22%2F%3E%3C%2Fsvg%3E");--fb-icon-content:"";',
			$declarations->toCss()
		);
	}

	public function testButtonDrawsTheIconOnlyOnceSet(): void
	{
		$css = ( new BaseStylesheet() )->css( [ new Button() ], false );

		self::assertStringContainsString(
			':where(.fb-button>.fb-button__link)::before{content:var(--fb-icon-content,none);flex:none;order:var(--fb-icon-order,0);'
			. 'width:var(--fb-icon-size,1em);height:var(--fb-icon-size,1em);background-color:currentColor;'
			. 'mask-image:var(--fb-icon);mask-position:center;mask-size:contain;mask-repeat:no-repeat;}',
			$css
		);
	}

	public function testOverlayOnlyWhenSomethingStylesIt(): void
	{
		self::assertSame( '<div class="fb-container">x</div>', $this->rendering->render( new Container(), [], 'x' ) );

		$html = $this->rendering->render(
			new Container(),
			[ 'fb' => TestLooks::of( [ 'overlayColor' => [ 'desktop' => [ 'kind' => 'custom', 'value' => '#000000' ] ] ] ) ],
			'x'
		);
		self::assertMatchesRegularExpression( '#^<div class="fb-container fb-[0-9a-z]{7}"><span class="fb-container__overlay" aria-hidden="true"></span>x</div>$#', $html );
	}

	public function testBackgroundVideoAndMissingMedia(): void
	{
		$this->attachment = (int) wp_insert_attachment(
			[ 'post_mime_type' => 'video/mp4', 'post_title' => 'fb test video', 'post_status' => 'inherit' ],
			'fb-test/clip.mp4'
		);

		$html = $this->rendering->render( new Container(), [ 'fb' => TestLooks::of( [ 'backgroundMedia' => [ 'id' => $this->attachment, 'kind' => 'video' ] ] ) ], '' );
		self::assertMatchesRegularExpression(
			'#^<div class="fb-container"><video class="fb-container__media" src="[^"]+/fb-test/clip\.mp4" autoplay muted loop playsinline aria-hidden="true"></video><span class="fb-container__overlay" aria-hidden="true"></span></div>$#',
			$html
		);

		// Asked for as an image: not one, so nothing (and no overlay).
		self::assertSame(
			'<div class="fb-container"></div>',
			$this->rendering->render( new Container(), [ 'fb' => TestLooks::of( [ 'backgroundMedia' => [ 'id' => $this->attachment, 'kind' => 'image' ] ] ) ], '' )
		);
	}
}
