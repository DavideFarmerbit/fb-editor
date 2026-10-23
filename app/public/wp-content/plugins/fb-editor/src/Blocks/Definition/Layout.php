<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

/**
 * Sidebar layout: panels in order.
 *
 * @implements \IteratorAggregate<int, Panel>
 */
final readonly class Layout implements \IteratorAggregate, \JsonSerializable
{
	/** @var list<Panel> */
	private array $panels;

	public function __construct( Panel ...$panels )
	{
		$this->panels = array_values( $panels );
	}

	/**
	 * @return \ArrayIterator<int, Panel>
	 */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator( $this->panels );
	}

	/**
	 * @return list<Panel>
	 */
	public function jsonSerialize(): array
	{
		return $this->panels;
	}
}
