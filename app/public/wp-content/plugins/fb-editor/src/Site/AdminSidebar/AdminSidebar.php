<?php

declare(strict_types=1);

namespace Fb\Site\AdminSidebar;

use Fb\Hookable;
use Fb\PluginPaths;
use Fb\RequestContext;

/**
 * Frontend only, for administrators: core's admin bar is replaced by a
 * sidebar that slides in from a corner button, with quick actions, the
 * template and parts the page is built from, and system facts.
 *
 * The bar is turned off (show_admin_bar()), not hidden with CSS: no bar,
 * no `margin-top` on <html>, no admin-bar stylesheet. Full-height layouts
 * look as they do for visitors.
 *
 * Turn off per site: add_filter( 'fb_admin_sidebar', '__return_false' );
 */
final readonly class AdminSidebar implements Hookable
{
	private const string HANDLE = 'fb-admin-sidebar';
	private const string ID     = 'fb-admin-sidebar';

	/** WP Fastest Cache's main class: its "clear cache" is offered when present. */
	private const string FASTEST_CACHE = 'WpFastestCache';

	public function __construct(
		private PluginPaths $paths,
	) {
	}

	public function register(): void
	{
		// Before template_redirect, where core sets the admin bar up.
		add_action( 'wp', $this->boot( ... ) );
	}

	private function boot(): void
	{
		if (
			RequestContext::Frontend !== RequestContext::current()
			|| ! is_admin_bar_showing()
			|| ! current_user_can( 'manage_options' )
			|| ! (bool) apply_filters( 'fb_admin_sidebar', true )
		) {
			return;
		}

		show_admin_bar( false );
		add_action( 'wp_enqueue_scripts', $this->enqueue( ... ) );
		add_action( 'wp_footer', $this->render( ... ) );
	}

	private function enqueue(): void
	{
		$asset = $this->paths->buildAsset( 'admin-sidebar' );
		if ( null === $asset ) {
			return;
		}

		wp_enqueue_script( self::HANDLE, $this->paths->url( 'build/admin-sidebar.js' ), $asset->dependencies, $asset->version, [ 'strategy' => 'defer' ] );
		wp_enqueue_style( self::HANDLE, $this->paths->url( 'build/admin-sidebar.css' ), [], $asset->version );
	}

	private function render(): void
	{
		$info = new SystemInfo();

		$header = has_site_icon()
			? sprintf( '<img class="fb-admin-sidebar__site-icon" src="%s" alt="" width="32" height="32">', esc_url( get_site_icon_url( 32 ) ) )
			: '';

		// Every piece is escaped where it's built (SidebarLink, InfoItem…).
		printf(
			'<button type="button" class="fb-admin-sidebar-toggle" aria-controls="%1$s" aria-expanded="false" aria-label="%2$s">%3$s</button>'
				. '<aside id="%1$s" class="fb-admin-sidebar" aria-label="%4$s" inert>'
				. '<div class="fb-admin-sidebar__header">%5$s<span>%6$s</span></div>'
				. '<div class="fb-admin-sidebar__content">%7$s%8$s%9$s%10$s</div>'
				. '</aside>',
			esc_attr( self::ID ),
			esc_attr__( 'Site admin', 'fb-editor' ),
			SidebarIcon::Menu->svg(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup.
			esc_attr__( 'Site admin', 'fb-editor' ),
			$header, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			esc_html( get_bloginfo( 'name' ) ),
			self::section( __( 'Quick actions', 'fb-editor' ), self::actions() ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			self::section( __( 'Template', 'fb-editor' ), self::links( ( new TemplateLinks() )->links() ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			self::section( __( 'System', 'fb-editor' ), '<div class="fb-admin-sidebar__info-grid">' . implode( '', array_map( static fn ( InfoItem $item ): string => $item->html(), $info->items() ) ) . '</div>' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			self::section( __( 'PHP extensions', 'fb-editor' ), '<p class="fb-admin-sidebar__extensions">' . esc_html( $info->extensions() ) . '</p>' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/** Dashboard, edit this page, clear cache (if available), log out. */
	private static function actions(): string
	{
		$html = self::links(
			[
				new SidebarLink( __( 'Dashboard', 'fb-editor' ), admin_url(), SidebarIcon::Dashboard ),
				...self::editLink(),
			]
		);

		if ( class_exists( self::FASTEST_CACHE ) ) {
			$html .= sprintf(
				'<button type="button" class="fb-admin-sidebar__action" data-fb-clear-cache="%s" data-fb-done="%s" data-fb-failed="%s">%s<span>%s</span></button>',
				esc_url( add_query_arg( [ 'action' => 'wpfc_delete_cache', 'nonce' => wp_create_nonce( 'wpfc' ) ], admin_url( 'admin-ajax.php' ) ) ),
				esc_attr__( 'Cache cleared', 'fb-editor' ),
				esc_attr__( 'Could not clear the cache', 'fb-editor' ),
				SidebarIcon::ClearCache->svg(),
				esc_html__( 'Clear cache', 'fb-editor' )
			);
		}

		return $html . self::links( [ new SidebarLink( __( 'Log out', 'fb-editor' ), wp_logout_url(), SidebarIcon::LogOut ) ] );
	}

	/**
	 * Edit what's on screen: the post, or the term of an archive.
	 *
	 * @return list<SidebarLink>
	 */
	private static function editLink(): array
	{
		$url = null;
		if ( is_singular() ) {
			$url = get_edit_post_link( get_queried_object_id(), 'raw' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			$url  = $term instanceof \WP_Term ? get_edit_term_link( $term, $term->taxonomy ) : null;
		}

		return is_string( $url ) && '' !== $url ? [ new SidebarLink( __( 'Edit this page', 'fb-editor' ), $url, SidebarIcon::Edit ) ] : [];
	}

	/**
	 * @param list<SidebarLink> $links
	 */
	private static function links( array $links ): string
	{
		return implode( '', array_map( static fn ( SidebarLink $link ): string => $link->html(), $links ) );
	}

	/** A titled section; nothing at all when it has no content. */
	private static function section( string $title, string $content ): string
	{
		return '' === $content ? '' : sprintf( '<section class="fb-admin-sidebar__section"><h2>%s</h2>%s</section>', esc_html( $title ), $content );
	}
}
