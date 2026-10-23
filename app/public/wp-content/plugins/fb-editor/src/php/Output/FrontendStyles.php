<?php

declare(strict_types=1);

namespace Fb\Output;

use Fb\Adapter\WpInternals;
use Fb\Css\CssLayer;
use Fb\Hookable;
use Fb\Presets\PresetRepository;

/**
 * Frontend CSS: replaces core's global styles with fb's own token sheet and
 * applies CoreStylePolicy to every other core stylesheet.
 */
final class FrontendStyles implements Hookable
{
	public const string HANDLE = 'fb';

	/** @var array<string, true> Handles already wrapped in the wp layer. */
	private array $layered = [];

	public function __construct(
		private readonly CoreStylePolicy $policy,
		private readonly PresetRepository $presets,
	) {
	}

	public function register(): void
	{
		// Core global styles + block-supports CSS: replaced by fb's own output.
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );

		// Emoji detection script + styles.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );

		// Layer order is set by first appearance. Core styles may print before
		// this statement, but they are all in `wp`, which must come first anyway.
		add_action( 'wp_head', $this->enqueueTokens( ... ), -1 );

		// Runs inside wp_print_styles(), after core inlined small stylesheets.
		add_action( 'wp_print_styles', $this->applyPolicy( ... ) );
		// Late (footer) styles are printed from this action at priority 10.
		add_action( 'wp_print_footer_scripts', $this->applyPolicy( ... ), 1 );

		add_filter( 'style_loader_tag', $this->layerLinkedStylesheet( ... ), 10, 3 );
	}

	private function enqueueTokens(): void
	{
		$declarations = '';
		foreach ( $this->presets->all() as $preset ) {
			$declarations .= "{$preset->cssVariable()}:{$preset->value};";
		}

		wp_register_style( self::HANDLE, false, [], null );
		wp_add_inline_style(
			self::HANDLE,
			CssLayer::orderStatement() . ( '' === $declarations ? '' : CssLayer::Tokens->wrap( ":root{{$declarations}}" ) )
		);
		wp_enqueue_style( self::HANDLE );
	}

	private function applyPolicy(): void
	{
		foreach ( wp_styles()->queue as $handle ) {
			if ( ! is_string( $handle ) ) {
				continue;
			}

			match ( $this->policy->decide( $handle ) ) {
				StyleAction::Remove => wp_dequeue_style( $handle ),
				StyleAction::Layer  => $this->layerInlineStyles( $handle ),
				StyleAction::Keep   => null,
			};
		}
	}

	private function layerInlineStyles( string $handle ): void
	{
		if ( isset( $this->layered[ $handle ] ) ) {
			return;
		}
		$this->layered[ $handle ] = true;

		$css = WpInternals::inlineStyles( $handle );
		if ( [] !== $css ) {
			WpInternals::replaceInlineStyles( $handle, [ CssLayer::Wp->wrap( implode( "\n", $css ) ) ] );
		}
	}

	/**
	 * A stylesheet still loaded from a file can't be wrapped in place:
	 * swap the <link> for an `@import … layer(wp)`.
	 */
	private function layerLinkedStylesheet( string $tag, string $handle, string $href ): string
	{
		if ( StyleAction::Layer !== $this->policy->decide( $handle ) || ! WpInternals::styleHasSource( $handle ) ) {
			return $tag;
		}

		return sprintf(
			'<style id="%s-css">@import url("%s") layer(%s);</style>' . "\n",
			esc_attr( $handle ),
			esc_url( $href ),
			CssLayer::Wp->value
		);
	}
}
