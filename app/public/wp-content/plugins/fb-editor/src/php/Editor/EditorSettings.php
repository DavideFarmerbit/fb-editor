<?php

declare(strict_types=1);

namespace Fb\Editor;

use Fb\Breakpoints\BreakpointList;

/**
 * Everything the editor JS receives as `window.fbEditorSettings`.
 * Mirrored by the `EditorSettings` type in src/js/types.ts.
 */
final readonly class EditorSettings implements \JsonSerializable
{
	public function __construct(
		public BreakpointList $breakpoints,
	) {
	}

	/**
	 * @return array{breakpoints: BreakpointList}
	 */
	public function jsonSerialize(): array
	{
		return [ 'breakpoints' => $this->breakpoints ];
	}
}
