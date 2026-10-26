<?php

declare(strict_types=1);

namespace Fb\Blocks\Variants;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Storage\ThemeDesignPostType;
use Fb\Breakpoints\BreakpointList;
use Fb\TypeScript\ExportToTypeScript;

/**
 * Saved variants: one post per block type and theme holding all of that
 * type's variants (a VariantSet), so they're edited and saved together.
 */
final readonly class BlockVariantsPostType extends ThemeDesignPostType
{
	#[ExportToTypeScript]
	public const string POST_TYPE = 'fb_block_variants';

	#[ExportToTypeScript]
	public const string REST_BASE = 'fb-block-variants';

	protected function postType(): string
	{
		return self::POST_TYPE;
	}

	protected function restBase(): string
	{
		return self::REST_BASE;
	}

	protected function label(): string
	{
		return __( 'Block variants', 'fb-editor' );
	}

	protected function sanitizeContent( mixed $json, BlockDefinition $definition, BreakpointList $breakpoints ): \JsonSerializable
	{
		return VariantSet::fromJson( $json, $definition, $breakpoints );
	}

	protected function emptyContent(): string
	{
		return '[]';
	}
}
