<?php

declare(strict_types=1);

namespace Fb\Output;

use Fb\Adapter\WpInternals;
use Fb\Css\CssLayer;

/**
 * Applies a StylePolicy to the style queue of one WP_Styles instance.
 *
 * Bound to a specific instance on purpose: the editor collects canvas
 * assets in a temporary WP_Styles, and the policy must not leak into
 * the admin page's own styles.
 */
final class StylePolicyApplier
{
	private ?int $boundStyles = null;

	/** @var array<string, true> Handles whose inline CSS is already layered. */
	private array $layered = [];

	public function __construct(
		private readonly StylePolicy $policy,
	) {
	}

	public function register(): void
	{
		add_filter( 'style_loader_tag', $this->layerLinkedStylesheet( ... ), 10, 3 );
	}

	/**
	 * Applies the policy to the current WP_Styles queue and binds the
	 * applier to that instance for the <link> rewriting below.
	 */
	public function apply(): void
	{
		$styles            = wp_styles();
		$this->boundStyles = spl_object_id( $styles );

		foreach ( $styles->queue as $handle ) {
			if ( ! is_string( $handle ) ) {
				continue;
			}

			match ( $this->policy->decide( $handle ) ) {
				StyleAction::Remove => $this->remove( $styles, $handle ),
				StyleAction::Layer  => $this->layerInlineStyles( $handle ),
				StyleAction::Keep   => null,
			};
		}

		// Dependencies are printed even when not queued: walk them too.
		foreach ( array_keys( $styles->registered ) as $handle ) {
			if ( StyleAction::Remove === $this->policy->decide( $handle ) && ! in_array( $handle, $styles->queue, true ) ) {
				$this->remove( $styles, $handle );
			}
		}
	}

	private function remove( \WP_Styles $styles, string $handle ): void
	{
		wp_dequeue_style( $handle );
		// Marking it done also suppresses it when another style depends on it.
		if ( ! in_array( $handle, $styles->done, true ) ) {
			$styles->done[] = $handle;
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
		if (
			spl_object_id( wp_styles() ) !== $this->boundStyles
			|| StyleAction::Layer !== $this->policy->decide( $handle )
			|| ! WpInternals::styleHasSource( $handle )
		) {
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
