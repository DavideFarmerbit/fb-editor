<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Definition\BlockDefinition;

/**
 * One PHP ↔ JS parity case: a definition plus raw base and state values
 * (as WordPress would hand them over) compiled for a fixed root class, with
 * the block type's defaults in the same shape.
 */
final readonly class StyleCase
{
	public const string ROOT_CLASS = 'fb-case';

	/** The class the case's variant (if any) is compiled on. */
	public const string VARIANT_CLASS = 'fb-case-variant';

	/**
	 * @param array<string, mixed> $base          Raw boundary data, on purpose.
	 * @param array<string, mixed> $defaults      The type defaults' base look.
	 * @param array<string, mixed> $states        Raw state looks, keyed by state.
	 * @param array<string, mixed> $defaultStates The type defaults' state looks.
	 * @param array<string, mixed>|null $variant  A variant's raw values (base + states) the block uses.
	 */
	public function __construct(
		public string $name,
		public BlockDefinition $definition,
		public array $base,
		public array $defaults = [],
		public array $states = [],
		public array $defaultStates = [],
		public ?array $variant = null,
	) {
	}

	/**
	 * The raw `fb` attribute.
	 */
	public function attribute(): \stdClass
	{
		return self::stateful( $this->base, $this->states );
	}

	public function defaultsAttribute(): \stdClass
	{
		return self::stateful( $this->defaults, $this->defaultStates );
	}

	/**
	 * @param array<string, mixed> $base
	 * @param array<string, mixed> $states
	 */
	private static function stateful( array $base, array $states ): \stdClass
	{
		$json = new \stdClass();
		if ( [] !== $base ) {
			$json->base = $base;
		}
		if ( [] !== $states ) {
			$json->states = $states;
		}

		return $json;
	}
}
