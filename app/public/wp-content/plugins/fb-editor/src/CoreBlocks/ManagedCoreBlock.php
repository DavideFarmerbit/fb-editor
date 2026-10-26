<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

/**
 * Core blocks fb keeps but takes ownership of: their core CSS is removed and
 * their generated classes are replaced with fb's own.
 *
 * Core blocks not listed here are left alone (e.g. navigation, whose scripts
 * depend on core's class names).
 */
enum ManagedCoreBlock: string
{
	case Paragraph    = 'core/paragraph';
	case Heading      = 'core/heading';
	case List         = 'core/list';
	case Image        = 'core/image';
	case Buttons      = 'core/buttons';
	case Button       = 'core/button';
	case PostContent  = 'core/post-content';
	case PostExcerpt  = 'core/post-excerpt';
	case PostTemplate = 'core/post-template';
	case PostTitle    = 'core/post-title';
	case Query        = 'core/query';
	case SiteTitle    = 'core/site-title';
	case TemplatePart = 'core/template-part';

	public static function fromStyleHandle( string $handle ): ?self
	{
		return str_starts_with( $handle, 'wp-block-' )
			? self::tryFrom( 'core/' . substr( $handle, strlen( 'wp-block-' ) ) )
			: null;
	}

	/** e.g. "post-title" */
	public function slug(): string
	{
		return substr( $this->value, strlen( 'core/' ) );
	}

	/** Class core puts on the block wrapper, e.g. "wp-block-post-title". */
	public function coreClass(): string
	{
		return 'wp-block-' . $this->slug();
	}

	/** Class fb puts there instead, e.g. "fb-post-title". */
	public function fbClass(): string
	{
		return 'fb-' . $this->slug();
	}

	public function cleanupScope(): CleanupScope
	{
		return match ( $this ) {
			self::Paragraph,
			self::Heading,
			self::Image,
			self::Button,
			self::PostExcerpt,
			self::PostTitle,
			self::SiteTitle    => CleanupScope::Subtree,
			self::PostTemplate => CleanupScope::RootAndPostItems,
			self::List,
			self::Buttons,
			self::PostContent,
			self::Query,
			self::TemplatePart => CleanupScope::Root,
		};
	}
}
