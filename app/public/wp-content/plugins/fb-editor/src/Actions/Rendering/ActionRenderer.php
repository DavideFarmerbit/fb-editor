<?php

declare(strict_types=1);

namespace Fb\Actions\Rendering;

use Fb\Actions\Catalog\ActionCatalog;
use Fb\Actions\Catalog\Effect;
use Fb\Actions\Catalog\Param;
use Fb\Actions\Rules\BlockActions;
use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Definition\Input\Target\TargetValue;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Hookable;
use Fb\PluginPaths;
use Fb\RequestContext;

/**
 * Frontend: puts every block's actions on the page. Its rules go on its
 * root as `data-fb-actions` (sanitized, without what markup alone does);
 * effects with markup (a link) add it inside the root, first; targets that
 * start closed are recorded for ActionsOnPage's CSS; the page script is
 * enqueued once some block needs it. The anchor class comes with the
 * block's other classes (BlockStyler).
 */
final readonly class ActionRenderer implements Hookable
{
	public const string ATTRIBUTE = 'data-fb-actions';

	private const string HANDLE = 'fb-actions';

	public function __construct(
		private ActionCatalog $catalog,
		private ActionsOnPage $page,
		private PluginPaths $paths,
		private BreakpointConfig $breakpoints,
	) {
	}

	public function register(): void
	{
		add_filter( 'render_block', $this->render( ... ), 10, 2 );
		// After every block rendered (footer scripts print at 20).
		add_action( 'wp_footer', $this->enqueue( ... ), 5 );
	}

	/**
	 * @param array<mixed> $block Core's parsed block (boundary).
	 */
	private function render( string $html, array $block ): string
	{
		if ( RequestContext::Frontend !== RequestContext::current() || '' === trim( $html ) ) {
			return $html;
		}

		$attrs   = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
		$actions = BlockActions::fromAttribute( $attrs[ BlockRegistry::ATTRIBUTE ] ?? null, $this->catalog );
		if ( [] === $actions->rules ) {
			return $html;
		}

		$forPage = [];
		$markup  = '';
		$text    = self::text( $html );
		foreach ( $actions->rules as $rule ) {
			foreach ( $rule->effects as $effect ) {
				if ( ! $effect->type instanceof Effect ) {
					continue;
				}
				$markup .= $effect->type->markup( $effect->params, $text ) ?? '';
				$hiding  = $effect->type->hidesTarget( $effect->params );
				$target  = $effect->params->get( Param::TARGET );
				if ( null !== $hiding && $target instanceof TargetValue && null !== $target->block ) {
					$this->page->hideUntilOpen( $target->block, $hiding );
				}
			}
			$pageRule = $rule->forPage();
			if ( null !== $pageRule ) {
				$forPage[] = $pageRule;
			}
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag() ) {
			return $html;
		}
		if ( [] !== $forPage ) {
			$processor->set_attribute( self::ATTRIBUTE, (string) wp_json_encode( $forPage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
			$this->page->needsRuntime();
		}
		$html = $processor->get_updated_html();

		if ( '' !== $markup ) {
			$this->page->hasLink();
			$html = self::insertInRoot( $html, $markup );
		}

		return $html;
	}

	private function enqueue(): void
	{
		if ( ! $this->page->usesRuntime() ) {
			return;
		}
		$asset = $this->paths->buildAsset( 'actions' );
		if ( null === $asset ) {
			return;
		}

		wp_enqueue_script( self::HANDLE, $this->paths->url( 'build/actions.js' ), $asset->dependencies, $asset->version, [ 'in_footer' => true, 'strategy' => 'defer' ] );
		// The breakpoints' media queries, for rules off at some breakpoints.
		$queries = [];
		foreach ( $this->breakpoints->breakpoints() as $breakpoint ) {
			$queries[ $breakpoint->id->value ] = $this->breakpoints->breakpoints()->bandQuery( $breakpoint ) ?? 'all';
		}
		wp_add_inline_script( self::HANDLE, 'window.fbActionBreakpoints = ' . wp_json_encode( $queries ) . ';', 'before' );
	}

	/** The block's text, one line: a fallback accessible name. */
	private static function text( string $html ): string
	{
		return trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
	}

	/**
	 * `$markup` right after the root's start tag. The tag is found by
	 * walking it, quotes included: attribute values may hold ">".
	 */
	private static function insertInRoot( string $html, string $markup ): string
	{
		$start = strpos( $html, '<' );
		if ( false === $start ) {
			return $html;
		}

		$quote  = null;
		$length = strlen( $html );
		for ( $i = $start + 1; $i < $length; $i++ ) {
			$char = $html[ $i ];
			if ( null !== $quote ) {
				$quote = $char === $quote ? null : $quote;
			} elseif ( '"' === $char || "'" === $char ) {
				$quote = $char;
			} elseif ( '>' === $char ) {
				return substr( $html, 0, $i + 1 ) . $markup . substr( $html, $i + 1 );
			}
		}

		return $html;
	}
}
