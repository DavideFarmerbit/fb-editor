<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Core blocks that may be inserted. Everything else is kept out of the
 * inserter (existing content using other blocks still renders).
 *
 * Content blocks get fb controls (see ManagedCoreBlock); structural ones
 * (template parts, query loop, navigation…) are needed by block themes.
 */
#[ExportToTypeScript]
enum AllowedCoreBlock: string
{
	// Content.
	case Paragraph = 'core/paragraph';
	case Heading   = 'core/heading';
	case List      = 'core/list';
	case ListItem  = 'core/list-item';
	case Image     = 'core/image';
	case Buttons   = 'core/buttons';
	case Button    = 'core/button';

	// Site structure.
	case TemplatePart = 'core/template-part';
	case PostContent  = 'core/post-content';
	case PostTitle    = 'core/post-title';
	case PostExcerpt  = 'core/post-excerpt';
	case Query        = 'core/query';
	case PostTemplate = 'core/post-template';
	case SiteTitle    = 'core/site-title';

	// Navigation.
	case Navigation        = 'core/navigation';
	case NavigationLink    = 'core/navigation-link';
	case NavigationSubmenu = 'core/navigation-submenu';
	case PageList          = 'core/page-list';
	case PageListItem      = 'core/page-list-item';
	case HomeLink          = 'core/home-link';

	// Patterns: synced patterns and pattern placeholders.
	case Block   = 'core/block';
	case Pattern = 'core/pattern';

	// Shown in place of content from unavailable blocks.
	case Missing = 'core/missing';
}
