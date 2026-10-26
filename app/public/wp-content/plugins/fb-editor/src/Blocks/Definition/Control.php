<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

use Fb\Blocks\Definition\Condition\Condition;
use Fb\Blocks\Definition\Input\Input;
use Fb\Styles\Output\StyleOutput;

/**
 * One setting of a block: what it's called, how it's edited (input), where
 * its value goes (output, on the root or an element), whether it varies per
 * breakpoint (scope), and when it's shown (condition).
 *
 * A control without an output is render-only: the block's render reads it.
 */
final readonly class Control implements \JsonSerializable
{
	public function __construct(
		public ControlId $id,
		public string $label,
		public Input $input,
		public ?StyleOutput $output = null,
		public ?ElementId $element = null,
		public Scope $scope = Scope::Responsive,
		public ?Condition $visibleWhen = null,
	) {
		if ( null !== $element && null === $output ) {
			throw new \InvalidArgumentException( "Control \"{$id}\": an element only makes sense with an output." );
		}
	}

	/**
	 * Whether it can be set per state (hover…): only per-breakpoint
	 * controls that write CSS. Markup-level settings (a tag) can't change
	 * on hover.
	 */
	public function isStateful(): bool
	{
		return Scope::Responsive === $this->scope && null !== $this->output;
	}

	/**
	 * @return array{id: ControlId, label: string, input: Input, output: ?StyleOutput, element: ?ElementId, scope: Scope, visibleWhen: ?Condition}
	 */
	public function jsonSerialize(): array
	{
		return [
			'id'          => $this->id,
			'label'       => $this->label,
			'input'       => $this->input,
			'output'      => $this->output,
			'element'     => $this->element,
			'scope'       => $this->scope,
			'visibleWhen' => $this->visibleWhen,
		];
	}
}
