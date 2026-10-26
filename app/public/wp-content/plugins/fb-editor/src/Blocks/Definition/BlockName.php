<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Block name: fb/section, or a core block fb manages (core/paragraph).
 * Either way the block's class is "fb-<name>" (fb-section, fb-paragraph):
 * on the frontend, managed core blocks carry fb's classes, not core's.
 */
final readonly class BlockName implements \JsonSerializable, \Stringable
{
	#[ExportToTypeScript]
	public const string NAMESPACE = 'fb';

	public const string CORE_NAMESPACE = 'core';

	public function __construct(
		public string $name,
		public string $namespace = self::NAMESPACE,
	) {
		if ( 1 !== preg_match( '/^[a-z][a-z0-9-]*$/', $name ) ) {
			throw new \InvalidArgumentException( "Invalid block name \"{$name}\"." );
		}
		if ( self::NAMESPACE !== $namespace && self::CORE_NAMESPACE !== $namespace ) {
			throw new \InvalidArgumentException( "Unsupported block namespace \"{$namespace}\"." );
		}
	}

	public static function core( string $name ): self
	{
		return new self( $name, self::CORE_NAMESPACE );
	}

	public function isCore(): bool
	{
		return self::CORE_NAMESPACE === $this->namespace;
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
		return $this->namespace . '/' . $this->name;
	}

	public function jsonSerialize(): string
	{
		return (string) $this;
	}
}
