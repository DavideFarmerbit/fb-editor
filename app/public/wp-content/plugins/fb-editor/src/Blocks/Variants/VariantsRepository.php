<?php

declare(strict_types=1);

namespace Fb\Blocks\Variants;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Storage\ThemeDesignPostType;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Theme\ThemeConfigFile;

/**
 * A block type's variants, from two sources, lowest first:
 *   1. the theme: "variants" → "<block name>" → list in fb.json
 *   2. saved: the block's BlockVariantsPostType post for the active theme
 * A saved variant with a theme variant's slug is laid over it.
 */
final class VariantsRepository
{
	/** @var array<string, VariantSet> Keyed by block name. */
	private array $merged = [];

	/**
	 * @param string|null $stylesheet Theme whose saved variants to read;
	 *     defaults to the active theme.
	 */
	public function __construct(
		private readonly ThemeConfigFile $file,
		private readonly BreakpointConfig $breakpoints,
		private readonly ?string $stylesheet = null,
	) {
	}

	public function for( BlockDefinition $definition ): VariantSet
	{
		return $this->merged[ (string) $definition->name ] ??= $this->theme( $definition )->overlay( $this->saved( $definition ) );
	}

	public function find( BlockDefinition $definition, VariantSlug $slug ): ?Variant
	{
		return $this->for( $definition )->get( $slug );
	}

	public function theme( BlockDefinition $definition ): VariantSet
	{
		$section = $this->file->section( 'variants' );
		$name    = (string) $definition->name;
		$json    = null !== $section && $section->isObject() && $section->has( $name ) ? $section->get( $name ) : null;

		return VariantSet::fromJson( $json?->decoded(), $definition, $this->breakpoints->breakpoints() );
	}

	public function saved( BlockDefinition $definition ): VariantSet
	{
		return VariantSet::fromJson(
			ThemeDesignPostType::savedContent( BlockVariantsPostType::POST_TYPE, $definition->name, $this->stylesheet ?? get_stylesheet() ),
			$definition,
			$this->breakpoints->breakpoints()
		);
	}
}
