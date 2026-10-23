<?php

declare(strict_types=1);

namespace Fb\TypeScript;

/**
 * Marks PHP as the single source of truth for something the editor's TS also
 * needs. `composer generate` writes it to `<Class>.generated.ts` next to the
 * PHP file; a PHPUnit test fails if any generated file is stale.
 *
 * - On an enum: exported as a TS enum with the same cases.
 * - On a class constant: exported as `export const NAME = …`. With
 *   `regExp: true`, a string constant is exported as a RegExp literal; the
 *   pattern must then be valid in both PCRE and JS (no delimiters, no flags).
 */
#[\Attribute( \Attribute::TARGET_CLASS | \Attribute::TARGET_CLASS_CONSTANT )]
final readonly class ExportToTypeScript
{
	public function __construct(
		public bool $regExp = false,
	) {
	}
}
