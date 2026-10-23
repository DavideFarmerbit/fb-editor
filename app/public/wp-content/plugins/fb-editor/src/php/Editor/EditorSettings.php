<?php

declare(strict_types=1);

namespace Fb\Editor;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Breakpoints\BreakpointList;

/**
 * Everything the editor JS receives as `window.fbEditorSettings`.
 * Mirrored by the `EditorSettings` class in src/js/settings.ts.
 */
final readonly class EditorSettings implements \JsonSerializable
{
	/** @var list<BlockDefinition> */
	public array $blocks;

	public function __construct(
		public BreakpointList $breakpoints,
		BlockDefinition ...$blocks,
	) {
		$this->blocks = array_values( $blocks );
	}

	/**
	 * @return array{breakpoints: BreakpointList, blocks: list<BlockDefinition>}
	 */
	public function jsonSerialize(): array
	{
		return [ 'breakpoints' => $this->breakpoints, 'blocks' => $this->blocks ];
	}
}
