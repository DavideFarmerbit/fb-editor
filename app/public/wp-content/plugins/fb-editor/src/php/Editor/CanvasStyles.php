<?php

declare(strict_types=1);

namespace Fb\Editor;

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

	private StylePolicyApplier $applier;

	public function __construct(
		private TokenStylesheet $tokens,
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
			wp_add_inline_style( self::HANDLE, $this->tokens->css( withCoreStyles: false ) );
		}
		wp_enqueue_style( self::HANDLE );

		$this->applier->apply();
	}
}
