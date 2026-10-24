<?php

declare(strict_types=1);

namespace Fb\Output;

use Fb\Blocks\Defaults\DefaultsStylesheet;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Hookable;
use Fb\RequestContext;

/**
 * Frontend CSS: replaces core's global stylesheet with TokenStylesheet and
 * applies FrontendStylePolicy to every other stylesheet.
 */
final readonly class FrontendStyles implements Hookable
{
	public const string HANDLE = 'fb';

	private StylePolicyApplier $applier;

	public function __construct(
		private TokenStylesheet $tokens,
		private InstanceStyleCollector $instances,
		private DefaultsStylesheet $defaults,
	) {
		$this->applier = new StylePolicyApplier( new FrontendStylePolicy() );
	}

	public function register(): void
	{
		// Core's global styles: TokenStylesheet prints the parts we keep.
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );

		// Emoji detection script + styles.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );

		// Layer order is set by first appearance. Core styles may print before
		// this sheet, but they are all in `wp`, which must come first anyway.
		add_action( 'wp_head', $this->enqueue( ... ), -1 );

		// Runs inside wp_print_styles(), after core inlined small stylesheets.
		add_action( 'wp_print_styles', $this->apply( ... ) );
		// Late (footer) styles are printed from this action at priority 10.
		add_action( 'wp_print_footer_scripts', $this->apply( ... ), 1 );

		$this->applier->register();
	}

	private function enqueue(): void
	{
		// Block themes render the whole template before <head>, so every
		// block instance on the page has been collected by now.
		wp_register_style( self::HANDLE, false, [], null );
		wp_add_inline_style(
			self::HANDLE,
			$this->tokens->css( withCoreStyles: true )
				. $this->defaults->css( $this->instances->definitions() )
				. $this->instances->css()
		);
		wp_enqueue_style( self::HANDLE );
	}

	private function apply(): void
	{
		// `wp_print_styles` also fires while the editor collects canvas assets.
		if ( RequestContext::Frontend === RequestContext::current() ) {
			$this->applier->apply();
		}
	}
}
