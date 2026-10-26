<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

/**
 * A named inner element of a block. Its class is "<block class>__<id>" on
 * the frontend; core blocks keep core's markup in the editor, so they name
 * the class the element has there (e.g. "wp-block-button__link").
 */
final readonly class Element
{
	public function __construct(
		public ElementId $id,
		public ?string $editorClass = null,
	) {
	}
}
