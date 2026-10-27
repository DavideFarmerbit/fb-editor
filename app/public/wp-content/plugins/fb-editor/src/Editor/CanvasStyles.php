<?php

declare(strict_types=1);

namespace Fb\Editor;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\BaseStyles\BaseStylesheet;
use Fb\Blocks\Hiding\Hiding;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Hookable;
use Fb\Output\CanvasStylePolicy;
use Fb\Output\StylePolicyApplier;
use Fb\Output\TokenStylesheet;
use Fb\RequestContext;

/**
 * Editor canvas (iframe) CSS: same layer order and tokens as the frontend,
 * CanvasStylePolicy applied to block stylesheets.
 *
 * Core builds the canvas assets by calling wp_print_styles() on a temporary
 * WP_Styles (see _wp_get_iframed_editor_assets()). In wp-admin and REST,
 * that call is what fires `wp_print_styles`: admin pages print their own
 * styles through `admin_print_styles` instead.
 */
final readonly class CanvasStyles implements Hookable
{
	private const string HANDLE = 'fb-canvas';

	/**
	 * The canvas draws no scrollbar: a classic (non-overlay) one takes its
	 * width from the page, so previewing LG at 992px would lay it out
	 * narrower and fire MD's queries. Only the bar goes: wheel, trackpad,
	 * keyboard and block dragging still scroll. `::-webkit-scrollbar` is
	 * for WebKit versions without `scrollbar-width`.
	 */
	private const string NO_SCROLLBAR = 'html{scrollbar-width:none;}html::-webkit-scrollbar{width:0;height:0;}';

	/**
	 * No post title above the content: with a block theme the template
	 * decides where (and whether) the title shows, so the canvas's own copy
	 * only pushes the real content down. It's edited from the header
	 * instead (PostTitle/post-title-field.tsx).
	 */
	private const string NO_POST_TITLE = '.editor-visual-editor__post-title-wrapper{display:none;}';

	private StylePolicyApplier $applier;

	public function __construct(
		private TokenStylesheet $tokens,
		private BlockRegistry $blocks,
		private BreakpointConfig $breakpoints,
	) {
		$this->applier = new StylePolicyApplier( new CanvasStylePolicy() );
	}

	public function register(): void
	{
		add_action( 'wp_print_styles', $this->apply( ... ) );
		$this->applier->register();
	}

	private function apply(): void
	{
		if ( ! RequestContext::current()->buildsEditorCanvas() ) {
			return;
		}

		if ( ! wp_style_is( self::HANDLE, 'registered' ) ) {
			wp_register_style( self::HANDLE, false, [], null );
			wp_add_inline_style(
				self::HANDLE,
				$this->tokens->css( withCoreStyles: false ) . ( new BaseStylesheet() )->css( $this->blocks->types(), editor: true )
					. Hiding::css( $this->breakpoints->breakpoints(), editor: true )
					. self::NO_SCROLLBAR
					. self::NO_POST_TITLE
			);
		}
		wp_enqueue_style( self::HANDLE );

		$this->applier->apply();
	}
}
