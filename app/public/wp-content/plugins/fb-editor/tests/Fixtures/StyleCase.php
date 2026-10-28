<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Definition\BlockDefinition;

/**
 * One PHP ↔ JS parity case: a definition plus raw base, state and other looks
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
	 * @param \stdClass|null       $variant       A variant's raw values the block uses (see looks()).
	 * @param list<array<string, mixed>> $looks   More raw looks of the block (combined states, conditions).
	 */
	public function __construct(
		public string $name,
		public BlockDefinition $definition,
		public array $base,
		public array $defaults = [],
		public array $states = [],
		public array $defaultStates = [],
		public ?\stdClass $variant = null,
		public array $looks = [],
	) {
	}

	/**
	 * The raw `fb` attribute.
	 */
	public function attribute(): \stdClass
	{
		return self::looks( $this->base, $this->states, $this->looks );
	}

	public function defaultsAttribute(): \stdClass
	{
		return self::looks( $this->defaults, $this->defaultStates );
	}

	/**
	 * Raw `{"looks": […]}` values: the base, one look per state, then any
	 * other looks as given.
	 *
	 * @param array<string, mixed>       $base
	 * @param array<string, mixed>       $states Keyed by state.
	 * @param list<array<string, mixed>> $more
	 */
	public static function looks( array $base, array $states = [], array $more = [] ): \stdClass
	{
		$looks = [];
		if ( [] !== $base ) {
			$looks[] = [ 'values' => $base ];
		}
		foreach ( $states as $state => $values ) {
			$looks[] = [ 'states' => [ $state ], 'values' => $values ];
		}

		$json = new \stdClass();
		if ( [] !== $looks || [] !== $more ) {
			$json->looks = [ ...$looks, ...$more ];
		}

		return $json;
	}
}
