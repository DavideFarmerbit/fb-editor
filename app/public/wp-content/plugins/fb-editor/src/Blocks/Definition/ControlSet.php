<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

/**
 * A block's controls, in declaration order, with unique ids.
 *
 * @implements \IteratorAggregate<int, Control>
 */
final readonly class ControlSet implements \IteratorAggregate, \JsonSerializable
{
	/** @var list<Control> */
	private array $controls;

	public function __construct( Control ...$controls )
	{
		$seen = [];
		foreach ( $controls as $control ) {
			if ( isset( $seen[ $control->id->value ] ) ) {
				throw new \InvalidArgumentException( "Duplicate control id \"{$control->id}\"." );
			}
			$seen[ $control->id->value ] = true;
		}

		$this->controls = array_values( $controls );
	}

	/**
	 * Merges control groups (e.g. a shared box-model group + block-specific
	 * controls) into one set.
	 */
	public function with( self ...$others ): self
	{
		$controls = $this->controls;
		foreach ( $others as $other ) {
			array_push( $controls, ...$other->controls );
		}

		return new self( ...$controls );
	}

	public function get( ControlId $id ): ?Control
	{
		foreach ( $this->controls as $control ) {
			if ( $control->id->equals( $id ) ) {
				return $control;
			}
		}

		return null;
	}

	public function has( ControlId $id ): bool
	{
		return null !== $this->get( $id );
	}

	/**
	 * @return \ArrayIterator<int, Control>
	 */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator( $this->controls );
	}

	/**
	 * @return list<Control>
	 */
	public function jsonSerialize(): array
	{
		return $this->controls;
	}
}
