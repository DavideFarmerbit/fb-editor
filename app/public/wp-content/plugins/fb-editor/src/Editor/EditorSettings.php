<?php

declare(strict_types=1);

namespace Fb\Editor;

use Fb\Actions\ActionCatalog;
use Fb\Blocks\Storage\BlockDesignInfo;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Looks\MediaCondition;
use Fb\Breakpoints\BreakpointList;
use Fb\Presets\Preset;

/**
 * Everything the editor JS receives as `window.fbEditorSettings`.
 * Mirrored by the `EditorSettings` class in src/js/settings.ts.
 */
final readonly class EditorSettings implements \JsonSerializable
{
	/**
	 * @param list<Preset>            $presets
	 * @param list<BlockDefinition>   $blocks
	 * @param list<BlockDesignInfo>   $designs
	 */
	public function __construct(
		public BreakpointList $breakpoints,
		public array $presets,
		public array $blocks,
		public array $designs,
		/** Triggers, conditions and effects the Actions panel offers. */
		public ActionCatalog $actions,
	) {
	}

	/**
	 * @return array{breakpoints: BreakpointList, presets: list<Preset>, blocks: list<BlockDefinition>, designs: list<BlockDesignInfo>, conditions: list<array<string, mixed>>, actions: ActionCatalog}
	 */
	public function jsonSerialize(): array
	{
		return [
			'breakpoints' => $this->breakpoints,
			'presets'     => $this->presets,
			'blocks'      => $this->blocks,
			'designs'     => $this->designs,
			// For the canvas preview (View menu): not tied to any block.
			'conditions'  => array_map( static fn ( MediaCondition $condition ): array => $condition->info(), MediaCondition::cases() ),
			'actions'     => $this->actions,
		];
	}
}
