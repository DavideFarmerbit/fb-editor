<?php

declare(strict_types=1);

namespace Fb\Editor;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Breakpoints\BreakpointList;
use Fb\Presets\Preset;

/**
 * Everything the editor JS receives as `window.fbEditorSettings`.
 * Mirrored by the `EditorSettings` class in src/js/settings.ts.
 */
final readonly class EditorSettings implements \JsonSerializable
{
	/**
	 * @param list<Preset>          $presets
	 * @param list<BlockDefinition> $blocks
	 */
	public function __construct(
		public BreakpointList $breakpoints,
		public array $presets,
		public array $blocks,
	) {
	}

	/**
	 * @return array{breakpoints: BreakpointList, presets: list<Preset>, blocks: list<BlockDefinition>}
	 */
	public function jsonSerialize(): array
	{
		return [ 'breakpoints' => $this->breakpoints, 'presets' => $this->presets, 'blocks' => $this->blocks ];
	}
}
