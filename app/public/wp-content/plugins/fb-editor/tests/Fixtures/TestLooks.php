<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

/**
 * Raw `{"looks": […]}` values for tests, written as a base plus per-state
 * values (the common case), with any other looks after.
 */
final class TestLooks
{
	/**
	 * @param array<string, mixed>       $base
	 * @param array<string, mixed>       $states Keyed by state.
	 * @param list<array<string, mixed>> $more
	 * @return array{looks?: list<array<string, mixed>>}
	 */
	public static function of( array $base, array $states = [], array $more = [] ): array
	{
		$looks = [];
		if ( [] !== $base ) {
			$looks[] = [ 'values' => $base ];
		}
		foreach ( $states as $state => $values ) {
			$looks[] = [ 'states' => [ $state ], 'values' => $values ];
		}
		$looks = [ ...$looks, ...$more ];

		return [] === $looks ? [] : [ 'looks' => $looks ];
	}
}
