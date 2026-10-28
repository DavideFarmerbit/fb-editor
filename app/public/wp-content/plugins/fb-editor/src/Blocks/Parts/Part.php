<?php

declare(strict_types=1);

namespace Fb\Blocks\Parts;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\ElementId;
use Fb\Styles\PseudoElement;
use Fb\Styles\Selector;

/**
 * A part of a block styled from the block's own sidebar: its ::before, its
 * image, its list markers… Declared by the block (or for every block), never
 * typed in: each kind offers only settings that work on it (see PartKind).
 *
 * Reached from the block root (states and conditions stay the block's:
 * hovering a card styles its image), in this order: a declared element (a
 * direct child), or a tag (descendant, or direct child); then a
 * pseudo-element.
 */
final readonly class Part implements \JsonSerializable
{
	public function __construct(
		public PartId $id,
		public string $label,
		public PartKind $kind,
		public ?ElementId $element = null,
		public ?string $tag = null,
		/** The tag is a direct child (not any descendant). */
		public bool $childTag = false,
		public ?PseudoElement $pseudoElement = null,
	) {
	}

	public static function before(): self
	{
		return new self( new PartId( 'before' ), '::before', PartKind::Pseudo, pseudoElement: PseudoElement::Before );
	}

	public static function after(): self
	{
		return new self( new PartId( 'after' ), '::after', PartKind::Pseudo, pseudoElement: PseudoElement::After );
	}

	/** The block's own marker (a list item). */
	public static function marker(): self
	{
		return new self( new PartId( 'marker' ), __( 'Marker', 'fb-editor' ), PartKind::Marker, pseudoElement: PseudoElement::Marker );
	}

	/** Its items' markers (a list). */
	public static function itemMarkers(): self
	{
		return new self( new PartId( 'markers' ), __( 'Markers', 'fb-editor' ), PartKind::Marker, tag: 'li', childTag: true, pseudoElement: PseudoElement::Marker );
	}

	/** One of its declared elements. */
	public static function element( string $id, string $label, ElementId $element ): self
	{
		return new self( new PartId( $id ), $label, PartKind::Element, element: $element );
	}

	/** Every descendant of a tag, e.g. an image block's <img>. */
	public static function tag( string $id, string $label, string $tag ): self
	{
		return new self( new PartId( $id ), $label, PartKind::Element, tag: $tag );
	}

	/**
	 * Its own settings, as a block definition of its own (so values, the
	 * sidebar and the compiler handle it like any block), with the block's
	 * states.
	 */
	public function definition( BlockDefinition $block ): BlockDefinition
	{
		return new BlockDefinition( $block->name, "{$block->title} › {$this->label}", $this->kind->controls(), $this->kind->layout(), [], $block->states );
	}

	/** Where it is, from the block root's selector (states already applied). */
	public function selector( Selector $root, BlockDefinition $block ): Selector
	{
		$selector = $root;
		if ( null !== $this->element ) {
			$selector = $selector->child( $block->name->elementClass( $this->element ) );
		}
		if ( null !== $this->tag ) {
			$selector = $this->childTag ? $selector->childTag( $this->tag ) : $selector->descendantTag( $this->tag );
		}

		return null === $this->pseudoElement ? $selector : $selector->withPseudoElement( $this->pseudoElement );
	}

	/**
	 * What the editor receives: the path is applied there the same way.
	 *
	 * @return array{id: PartId, label: string, kind: PartKind, element: ?ElementId, tag: ?string, childTag: bool, pseudoElement: ?PseudoElement}
	 */
	public function jsonSerialize(): array
	{
		return [
			'id'            => $this->id,
			'label'         => $this->label,
			'kind'          => $this->kind,
			'element'       => $this->element,
			'tag'           => $this->tag,
			'childTag'      => $this->childTag,
			'pseudoElement' => $this->pseudoElement,
		];
	}
}
