<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

use Fb\Blocks\Definition\ElementId;
use Fb\Styles\DeclarationSet;

/**
 * One functional base rule of a managed core block (what core's own CSS
 * used to provide). Targets the root or an element, optionally a
 * descendant tag; BaseStylesheet resolves classes per context, since core
 * blocks keep core's element classes in the editor.
 */
final readonly class BaseStyle
{
	public function __construct(
		public DeclarationSet $declarations,
		public ?ElementId $element = null,
		public ?string $descendantTag = null,
	) {
	}
}
