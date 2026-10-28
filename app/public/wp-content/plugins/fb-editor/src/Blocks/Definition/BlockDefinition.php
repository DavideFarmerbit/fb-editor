<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

use Fb\Blocks\Looks\MediaCondition;
use Fb\Blocks\States\BlockState;

/**
 * Everything the system needs to know about a block type: its controls,
 * their sidebar layout and its named elements.
 *
 * Validated on construction, so a typo in a block fails loudly at load time
 * instead of silently dropping CSS:
 * - every control is placed in exactly one panel, and panels reference
 *   only existing controls;
 * - conditions reference only existing controls (and not their own);
 * - outputs target only declared elements.
 */
final readonly class BlockDefinition implements \JsonSerializable
{
	/** @var list<Element> */
	public array $elements;

	/** @var list<BlockState> In cascade order. */
	public array $states;

	/**
	 * @param list<ElementId|Element> $elements Plain ids: same class in the editor.
	 * @param list<BlockState>        $states   States it can be styled for.
	 */
	public function __construct(
		public BlockName $name,
		public string $title,
		public ControlSet $controls,
		public Layout $layout,
		array $elements = [],
		array $states = [],
	) {
		$this->elements = array_map(
			static fn ( ElementId|Element $element ): Element => $element instanceof Element ? $element : new Element( $element ),
			$elements
		);
		$this->states   = BlockState::ordered( $states );
		$this->validate();
	}

	/**
	 * A copy with more controls, in a panel of their own (e.g. the
	 * Container item controls every block gets).
	 */
	public function withControls( ControlSet $controls, Panel $panel ): self
	{
		return new self( $this->name, $this->title, $this->controls->with( $controls ), $this->layout->with( $panel ), $this->elements, $this->states );
	}

	public function supports( BlockState $state ): bool
	{
		return in_array( $state, $this->states, true );
	}

	public function element( ElementId $id ): ?Element
	{
		foreach ( $this->elements as $element ) {
			if ( $element->id->equals( $id ) ) {
				return $element;
			}
		}

		return null;
	}

	public function hasElement( ElementId $id ): bool
	{
		foreach ( $this->elements as $element ) {
			if ( $element->id->equals( $id ) ) {
				return true;
			}
		}

		return false;
	}

	private function validate(): void
	{
		$placed = [];
		foreach ( $this->layout as $panel ) {
			foreach ( $panel->controls as $id ) {
				if ( ! $this->controls->has( $id ) ) {
					throw new \InvalidArgumentException( "{$this->name}: panel \"{$panel->label}\" references unknown control \"{$id}\"." );
				}
				if ( isset( $placed[ $id->value ] ) ) {
					throw new \InvalidArgumentException( "{$this->name}: control \"{$id}\" is placed in more than one panel." );
				}
				$placed[ $id->value ] = true;
			}
		}

		foreach ( $this->controls as $control ) {
			if ( ! isset( $placed[ $control->id->value ] ) ) {
				throw new \InvalidArgumentException( "{$this->name}: control \"{$control->id}\" is not placed in any panel." );
			}
			if ( null !== $control->element && ! $this->hasElement( $control->element ) ) {
				throw new \InvalidArgumentException( "{$this->name}: control \"{$control->id}\" targets undeclared element \"{$control->element}\"." );
			}
			foreach ( $control->visibleWhen?->references() ?? [] as $reference ) {
				if ( ! $this->controls->has( $reference ) ) {
					throw new \InvalidArgumentException( "{$this->name}: condition of \"{$control->id}\" references unknown control \"{$reference}\"." );
				}
				if ( $reference->equals( $control->id ) ) {
					throw new \InvalidArgumentException( "{$this->name}: condition of \"{$control->id}\" references itself." );
				}
			}
		}
	}

	/**
	 * What the editor receives for this block type.
	 *
	 * Class names are computed here, so the editor never re-derives them.
	 *
	 * @return array{name: BlockName, title: string, isCore: bool, className: string, controls: ControlSet, layout: Layout, elements: list<array{id: ElementId, className: string, editorClassName: string}>, states: list<array{id: BlockState, label: string, pseudoClass: string, editorPseudoClass: string, previewClass: string}>, conditions: list<array{id: MediaCondition, label: string, mediaQuery: string}>}
	 */
	public function jsonSerialize(): array
	{
		return [
			'name'      => $this->name,
			'title'     => $this->title,
			'isCore'    => $this->name->isCore(),
			'className' => $this->name->cssClass(),
			'controls'  => $this->controls,
			'layout'    => $this->layout,
			'elements'  => array_map(
				fn ( Element $element ): array => [
					'id'              => $element->id,
					'className'       => $this->name->elementClass( $element->id ),
					'editorClassName' => $element->editorClass ?? $this->name->elementClass( $element->id ),
				],
				$this->elements
			),
			'states'    => array_map(
				static fn ( BlockState $state ): array => [
					'id'                => $state,
					'label'             => $state->label(),
					'pseudoClass'       => $state->pseudoClass(),
					'editorPseudoClass' => $state->editorPseudoClass(),
					'previewClass'      => $state->previewClass(),
				],
				$this->states
			),
			// Every block can have looks for any condition: listed here so the
			// editor gets labels and queries from PHP.
			'conditions' => array_map(
				static fn ( MediaCondition $condition ): array => [
					'id'         => $condition,
					'label'      => $condition->label(),
					'mediaQuery' => $condition->mediaQuery(),
				],
				MediaCondition::cases()
			),
		];
	}
}
