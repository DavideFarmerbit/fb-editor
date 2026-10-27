<?php

declare(strict_types=1);

namespace Fb\Site\AdminSidebar;

/**
 * One system fact. A warning (debug on, search engines discouraged) is
 * highlighted: easy to forget on a live site.
 */
final readonly class InfoItem
{
	public function __construct(
		public string $label,
		public string $value,
		public bool $warning = false,
	) {
	}

	public function html(): string
	{
		return sprintf(
			'<div class="fb-admin-sidebar__info%s"><span class="fb-admin-sidebar__info-label">%s</span><span class="fb-admin-sidebar__info-value">%s</span></div>',
			$this->warning ? ' is-warning' : '',
			esc_html( $this->label ),
			esc_html( $this->value )
		);
	}
}
