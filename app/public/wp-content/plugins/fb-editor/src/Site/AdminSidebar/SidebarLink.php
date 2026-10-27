<?php

declare(strict_types=1);

namespace Fb\Site\AdminSidebar;

/**
 * One link in the sidebar.
 */
final readonly class SidebarLink
{
	public function __construct(
		public string $label,
		public string $url,
		public SidebarIcon $icon,
		/** Opens beside the page (editors), not in place of it. */
		public bool $newTab = false,
		/** Hover text, e.g. where a template comes from. */
		public ?string $hint = null,
	) {
	}

	public function html(): string
	{
		return sprintf(
			'<a class="fb-admin-sidebar__action" href="%s"%s%s>%s<span>%s</span></a>',
			esc_url( $this->url ),
			$this->newTab ? ' target="_blank" rel="noopener"' : '',
			null === $this->hint ? '' : sprintf( ' title="%s"', esc_attr( $this->hint ) ),
			$this->icon->svg(),
			esc_html( $this->label )
		);
	}
}
