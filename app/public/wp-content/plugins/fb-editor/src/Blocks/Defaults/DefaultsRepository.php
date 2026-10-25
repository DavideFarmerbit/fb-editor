<?php

declare(strict_types=1);

namespace Fb\Blocks\Defaults;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Values\BlockValues;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Theme\ThemeConfigFile;

/**
 * A block type's defaults, from two sources, lowest first:
 *   1. the theme: "defaults" → "<block name>" in fb.json (version-controlled)
 *   2. saved: the block's BlockDefaultsPostType post for the active theme
 * Saved values win per control and breakpoint.
 */
final class DefaultsRepository
{
	/** @var array<string, BlockValues> Keyed by block name. */
	private array $merged = [];

	/**
	 * @param string|null $stylesheet Theme whose saved defaults to read;
	 *     defaults to the active theme.
	 */
	public function __construct(
		private readonly ThemeConfigFile $file,
		private readonly BreakpointConfig $breakpoints,
		private readonly ?string $stylesheet = null,
	) {
	}

	public function for( BlockDefinition $definition ): BlockValues
	{
		return $this->merged[ (string) $definition->name ] ??= $this->theme( $definition )->overlay( $this->saved( $definition ) );
	}

	public function theme( BlockDefinition $definition ): BlockValues
	{
		$section = $this->file->section( 'defaults' );
		$name    = (string) $definition->name;
		$json    = null !== $section && $section->isObject() && $section->has( $name ) ? $section->get( $name ) : null;

		return BlockValues::fromAttribute( $json?->decoded(), $definition, $this->breakpoints->breakpoints() );
	}

	public function saved( BlockDefinition $definition ): BlockValues
	{
		$posts = get_posts(
			[
				'post_type'        => BlockDefaultsPostType::POST_TYPE,
				'name'             => BlockDefaultsPostType::slug( $definition->name, $this->stylesheet ?? get_stylesheet() ),
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'suppress_filters' => false,
			]
		);
		$post = $posts[0] ?? null;

		return BlockValues::fromAttribute(
			$post instanceof \WP_Post ? json_decode( $post->post_content ) : null,
			$definition,
			$this->breakpoints->breakpoints()
		);
	}
}
