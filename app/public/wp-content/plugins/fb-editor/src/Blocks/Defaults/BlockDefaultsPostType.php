<?php

declare(strict_types=1);

namespace Fb\Blocks\Defaults;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Storage\ThemeDesignPostType;
use Fb\Blocks\Values\StatefulValues;
use Fb\Breakpoints\BreakpointList;
use Fb\TypeScript\ExportToTypeScript;

/**
 * Saved block type defaults: one post per block type and theme, content
 * shaped like a block's own values (`{"base": …, "states": …}`).
 */
final readonly class BlockDefaultsPostType extends ThemeDesignPostType
{
	#[ExportToTypeScript]
	public const string POST_TYPE = 'fb_block_default';

	#[ExportToTypeScript]
	public const string REST_BASE = 'fb-block-defaults';

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
		return __( 'Block defaults', 'fb-editor' );
	}

	protected function sanitizeContent( mixed $json, BlockDefinition $definition, BreakpointList $breakpoints ): \JsonSerializable
	{
		return StatefulValues::fromJson( $json, $definition, $breakpoints );
	}

	protected function emptyContent(): string
	{
		return '{}';
	}
}
