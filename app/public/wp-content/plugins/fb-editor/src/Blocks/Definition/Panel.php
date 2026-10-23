<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

/**
 * A collapsible group of controls in the block sidebar.
 */
final readonly class Panel implements \JsonSerializable
{
	/** @var non-empty-list<ControlId> */
	public array $controls;

	public function __construct(
		public string $label,
		ControlId $control,
		ControlId ...$more,
	) {
		$this->controls = [ $control, ...array_values( $more ) ];
	}

	/**
	 * @return array{label: string, controls: non-empty-list<ControlId>}
	 */
	public function jsonSerialize(): array
	{
		return [ 'label' => $this->label, 'controls' => $this->controls ];
	}
}
