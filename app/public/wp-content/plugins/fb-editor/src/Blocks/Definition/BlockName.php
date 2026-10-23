<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Block name, e.g. fb/section → class "fb-section".
 */
final readonly class BlockName implements \JsonSerializable, \Stringable
{
	#[ExportToTypeScript]
	public const string NAMESPACE = 'fb';

	public function __construct(
		public string $name,
	) {
		if ( 1 !== preg_match( '/^[a-z][a-z0-9-]*$/', $name ) ) {
			throw new \InvalidArgumentException( "Invalid block name \"{$name}\"." );
		}
	}

	public function cssClass(): string
	{
		return self::NAMESPACE . '-' . $this->name;
	}

	public function elementClass( ElementId $element ): string
	{
		return $this->cssClass() . '__' . $element->value;
	}

	public function __toString(): string
	{
		return self::NAMESPACE . '/' . $this->name;
	}

	public function jsonSerialize(): string
	{
		return (string) $this;
	}
}
