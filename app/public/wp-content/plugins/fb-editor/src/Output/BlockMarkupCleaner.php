<?php

declare(strict_types=1);

namespace Fb\Output;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Rendering\InstanceStyleCollector;
use Fb\Blocks\Values\BlockValues;
use Fb\Blocks\Values\InstanceClass;
use Fb\Breakpoints\BreakpointConfig;
use Fb\CoreBlocks\CleanupScope;
use Fb\CoreBlocks\ManagedCoreBlock;
use Fb\Hookable;
use Fb\RequestContext;

/**
 * Frontend only: replaces core's generated classes on managed core blocks.
 *
 *   <h2 class="wp-block-post-title has-text-align-center my-class">
 *   → <h2 class="fb-post-title my-class">
 *
 * Classes the user typed into the block ("Additional CSS class") are kept.
 * Other attributes (id, aria-*, href…) are never touched. Core blocks with
 * fb controls also get their instance class, and their CSS is collected.
 */
final readonly class BlockMarkupCleaner implements Hookable
{
	/** Class name prefixes core generates and fb replaces. */
	private const array GENERATED_PREFIXES = [
		'wp-block-',
		'wp-container-',
		'wp-elements-',
		'wp-element-',
		'is-layout-',
		'is-style-',
		'has-',
		'align',
	];

	public function __construct(
		private BlockRegistry $blocks,
		private BreakpointConfig $breakpoints,
		private InstanceStyleCollector $instances,
	) {
	}

	public function register(): void
	{
		// `render_block_{name}` runs after the generic `render_block`, and core
		// adds classes there too (e.g. paragraph's "wp-block-paragraph"), so
		// hook it as late as possible.
		foreach ( ManagedCoreBlock::cases() as $managed ) {
			add_filter(
				"render_block_{$managed->value}",
				fn ( string $html, array $block ): string => $this->clean( $managed, $html, $block ),
				PHP_INT_MAX,
				2
			);
		}
	}

	/**
	 * @param array<mixed> $block Core's parsed block (boundary).
	 */
	private function clean( ManagedCoreBlock $managed, string $html, array $block ): string
	{
		// The editor (server-side previews via REST) relies on core's classes.
		if ( RequestContext::Frontend !== RequestContext::current() || '' === trim( $html ) ) {
			return $html;
		}

		$userClasses = self::userClasses( $block );
		$processor   = new \WP_HTML_Tag_Processor( $html );

		if ( ! $processor->next_tag() ) {
			return $html;
		}

		// Only treat the first tag as the wrapper if it's really this block's
		// (static blocks saved without wrapper markup don't have one).
		if ( self::classes( $processor )->contains( $managed->coreClass() ) ) {
			$classes = new ClassList( $managed->fbClass() );
			$instance = $this->instanceClass( $managed, $block );
			if ( null !== $instance ) {
				$classes = $classes->with( new ClassList( $instance ) );
			}
			self::setClasses( $processor, $classes->with( $userClasses ) );
		}

		match ( $managed->cleanupScope() ) {
			CleanupScope::Root             => null,
			CleanupScope::RootAndPostItems => self::cleanPostItems( $processor, $managed ),
			CleanupScope::Subtree          => self::cleanSubtree( $processor, $managed ),
		};

		return $processor->get_updated_html();
	}

	/**
	 * For core blocks fb has controls for: registers the instance's CSS and
	 * returns its class, or null when its values produce no CSS.
	 *
	 * @param array<mixed> $block
	 */
	private function instanceClass( ManagedCoreBlock $managed, array $block ): ?string
	{
		$coreBlock = $this->blocks->coreBlock( $managed );
		if ( null === $coreBlock ) {
			return null;
		}

		$attrs      = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
		$definition = $coreBlock->definition();
		$values     = BlockValues::fromAttribute( $attrs[ BlockRegistry::ATTRIBUTE ] ?? null, $definition, $this->breakpoints->breakpoints() );
		$instance   = InstanceClass::of( $values );

		return $this->instances->add( $instance, $definition, $values ) ? $instance->value : null;
	}

	/**
	 * Post template <li>s carry post_class() output
	 * ("wp-block-post post-1 type-post status-publish …").
	 */
	private static function cleanPostItems( \WP_HTML_Tag_Processor $processor, ManagedCoreBlock $block ): void
	{
		// Items of nested post templates were already cleaned, so only this
		// block's own items still carry "wp-block-post".
		while ( $processor->next_tag( [ 'tag_name' => 'LI', 'class_name' => 'wp-block-post' ] ) ) {
			self::setClasses( $processor, new ClassList( $block->fbClass() . '__item' ) );
		}
	}

	private static function cleanSubtree( \WP_HTML_Tag_Processor $processor, ManagedCoreBlock $block ): void
	{
		$coreElementPrefix = $block->coreClass() . '__';

		while ( $processor->next_tag() ) {
			$kept = [];
			foreach ( self::classes( $processor )->names as $class ) {
				if ( str_starts_with( $class, $coreElementPrefix ) ) {
					// wp-block-post-excerpt__more-link → fb-post-excerpt__more-link
					$kept[] = $block->fbClass() . '__' . substr( $class, strlen( $coreElementPrefix ) );
				} elseif ( ! self::isGenerated( $class ) ) {
					$kept[] = $class;
				}
			}
			self::setClasses( $processor, new ClassList( ...$kept ) );
		}
	}

	private static function isGenerated( string $class ): bool
	{
		foreach ( self::GENERATED_PREFIXES as $prefix ) {
			if ( str_starts_with( $class, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Classes from the block's `className` attribute. Core block supports
	 * also inject generated classes there (e.g. "wp-elements-1"), so those
	 * are filtered out the same way.
	 *
	 * @param array<mixed> $block
	 */
	private static function userClasses( array $block ): ClassList
	{
		$attrs     = $block['attrs'] ?? null;
		$className = is_array( $attrs ) ? ( $attrs['className'] ?? null ) : null;

		if ( ! is_string( $className ) ) {
			return new ClassList();
		}

		return new ClassList(
			...array_filter( ClassList::parse( $className )->names, static fn ( string $class ): bool => ! self::isGenerated( $class ) )
		);
	}

	private static function classes( \WP_HTML_Tag_Processor $processor ): ClassList
	{
		$class = $processor->get_attribute( 'class' );

		return is_string( $class ) ? ClassList::parse( $class ) : new ClassList();
	}

	private static function setClasses( \WP_HTML_Tag_Processor $processor, ClassList $classes ): void
	{
		if ( $classes->isEmpty() ) {
			$processor->remove_attribute( 'class' );
		} else {
			$processor->set_attribute( 'class', (string) $classes );
		}
	}
}
