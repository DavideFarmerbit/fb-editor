<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Definition\BlockDefinition;

/**
 * One PHP ↔ JS parity case: a definition plus a raw `fb` attribute (as
 * WordPress would hand it over) compiled for a fixed root class.
 */
final readonly class StyleCase
{
	public const string ROOT_CLASS = 'fb-case';

	/**
	 * @param array<string, mixed> $attribute Raw boundary data, on purpose.
	 * @param array<string, mixed> $defaults  The block type's defaults, same shape.
	 */
	public function __construct(
		public string $name,
		public BlockDefinition $definition,
		public array $attribute,
		public array $defaults = [],
	) {
	}
}
