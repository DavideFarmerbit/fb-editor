<?php

declare(strict_types=1);

namespace Fb\Blocks\Defaults;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Storage\ThemeDesignPostType;
use Fb\Blocks\Values\StatefulValues;
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
	/** @var array<string, StatefulValues> Keyed by block name. */
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

	public function for( BlockDefinition $definition ): StatefulValues
	{
		return $this->merged[ (string) $definition->name ] ??= $this->theme( $definition )->overlay( $this->saved( $definition ) );
	}

	public function theme( BlockDefinition $definition ): StatefulValues
	{
		$section = $this->file->section( 'defaults' );
		$name    = (string) $definition->name;
		$json    = null !== $section && $section->isObject() && $section->has( $name ) ? $section->get( $name ) : null;

		return StatefulValues::fromJson( $json?->decoded(), $definition, $this->breakpoints->breakpoints() );
	}

	public function saved( BlockDefinition $definition ): StatefulValues
	{
		return StatefulValues::fromJson(
			ThemeDesignPostType::savedContent( BlockDefaultsPostType::POST_TYPE, $definition->name, $this->stylesheet ?? get_stylesheet() ),
			$definition,
			$this->breakpoints->breakpoints()
		);
	}
}
