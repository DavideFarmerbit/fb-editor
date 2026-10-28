<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Looks\Look;
use Fb\Blocks\Looks\LookKey;
use Fb\Blocks\Looks\MediaCondition;
use Fb\Blocks\States\BlockState;
use Fb\Breakpoints\BreakpointList;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * Everything one source styles a block with: its looks (see LookKey), the
 * base first. Blocks (their `fb` attribute), type defaults and variants
 * have this shape:
 *
 *   {"looks": [{"values": {…}}, {"states": ["hover"], "values": {…}}]}
 *
 * Dropped when parsed: looks requiring a state the block type doesn't
 * support, or an unknown condition; outside the base, controls that can't
 * vary per look (see Control::isStateful()); empty looks; repeated looks
 * (the first one counts).
 */
final readonly class StatefulValues implements \JsonSerializable
{
	/**
	 * @param list<Look> $looks Not the base; in cascade order (LookKey::compare()), none empty, unique keys. Private storage.
	 */
	private function __construct(
		private BlockDefinition $definition,
		public BlockValues $base,
		private array $looks,
	) {
	}

	public static function empty( BlockDefinition $definition, BreakpointList $breakpoints ): self
	{
		return new self( $definition, BlockValues::empty( $definition, $breakpoints ), [] );
	}

	/**
	 * @param mixed $json   Decoded JSON (a block attribute, a saved default…).
	 * @param bool  $placed Keep controls that belong to the block's place
	 *     (only a block's own attribute has them; see Placement).
	 */
	public static function fromJson( mixed $json, BlockDefinition $definition, BreakpointList $breakpoints, bool $placed = true ): self
	{
		$root = JsonValue::of( $json, 'fb' );
		if ( ! $root->isObject() || ! $root->has( 'looks' ) ) {
			return self::empty( $definition, $breakpoints );
		}

		try {
			$items = $root->get( 'looks' )->list();
		} catch ( InvalidJson ) {
			return self::empty( $definition, $breakpoints );
		}

		$base  = null;
		$looks = [];
		foreach ( $items as $item ) {
			$key = self::parseKey( $item, $definition );
			if ( null === $key || ( $key->isBase() ? null !== $base : isset( $looks[ $key->id() ] ) ) ) {
				continue;
			}
			$values = BlockValues::fromAttribute( $item->has( 'values' ) ? $item->get( 'values' )->decoded() : null, $definition, $breakpoints );
			if ( $key->isBase() ) {
				$base = $placed ? $values : $values->withoutPlaced();
				continue;
			}
			$values = $values->onlyStateful();
			if ( ! $values->isEmpty() ) {
				$looks[ $key->id() ] = new Look( $key, $values );
			}
		}

		return new self( $definition, $base ?? BlockValues::empty( $definition, $breakpoints ), self::sorted( array_values( $looks ) ) );
	}

	/**
	 * A look's key, or null when it can't apply to this block type (a
	 * state it doesn't support, an unknown condition, a malformed entry).
	 */
	private static function parseKey( JsonValue $item, BlockDefinition $definition ): ?LookKey
	{
		if ( ! $item->isObject() ) {
			return null;
		}

		try {
			$states = [];
			foreach ( $item->has( 'states' ) ? $item->get( 'states' )->list() : [] as $value ) {
				$state = BlockState::tryFrom( $value->string() );
				if ( null === $state || ! in_array( $state, $definition->states, true ) ) {
					return null;
				}
				$states[] = $state;
			}

			$conditions = [];
			foreach ( $item->has( 'when' ) ? $item->get( 'when' )->list() : [] as $value ) {
				$condition = MediaCondition::tryFrom( $value->string() );
				if ( null === $condition ) {
					return null;
				}
				$conditions[] = $condition;
			}
		} catch ( InvalidJson ) {
			return null;
		}

		return new LookKey( $states, $conditions );
	}

	/** One look's values; empty for looks it doesn't set. */
	public function look( LookKey $key ): BlockValues
	{
		if ( $key->isBase() ) {
			return $this->base;
		}
		foreach ( $this->looks as $look ) {
			if ( $look->key->equals( $key ) ) {
				return $look->values;
			}
		}

		return BlockValues::empty( $this->definition, $this->base->breakpoints() );
	}

	/**
	 * Every look, the base first, in cascade order.
	 *
	 * @return list<Look>
	 */
	public function looks(): array
	{
		return [ new Look( LookKey::base(), $this->base ), ...$this->looks ];
	}

	public function isEmpty(): bool
	{
		return $this->base->isEmpty() && [] === $this->looks;
	}

	/** Whether the control is set in any look, at any breakpoint. */
	public function has( ControlId $id ): bool
	{
		foreach ( $this->looks() as $look ) {
			if ( ! $look->values->get( $id )->isEmpty() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Where values come from for this source in a look, with weaker sources
	 * after it, in CSS cascade order (strongest first): every look applying
	 * within `$key` that has states, source by source, then those without.
	 * Any state beats any base; within a source, more specific looks first:
	 *
	 *   this hover|dark → this hover → weaker hover|dark → weaker hover
	 *   → this dark → this base → weaker dark → weaker base
	 */
	public function chain( LookKey $key, self ...$weaker ): ValueChain
	{
		$sources = [ $this, ...$weaker ];
		$values  = [];
		foreach ( [ true, false ] as $withStates ) {
			foreach ( $sources as $source ) {
				foreach ( array_reverse( $source->looks() ) as $look ) {
					if ( $look->key->hasStates() === $withStates && $look->key->appliesWithin( $key ) ) {
						$values[] = $look->values;
					}
				}
			}
		}

		return new ValueChain( $this->definition, ...$values );
	}

	/**
	 * `$over` laid on top, per look, control and breakpoint (saved
	 * defaults over the theme's).
	 */
	public function overlay( self $over ): self
	{
		$keys = [];
		foreach ( [ ...$this->looks, ...$over->looks ] as $look ) {
			$keys[ $look->key->id() ] = $look->key;
		}

		$looks = [];
		foreach ( $keys as $key ) {
			$merged = $this->look( $key )->overlay( $over->look( $key ) );
			if ( ! $merged->isEmpty() ) {
				$looks[] = new Look( $key, $merged );
			}
		}

		return new self( $this->definition, $this->base->overlay( $over->base ), self::sorted( $looks ) );
	}

	/**
	 * Canonical form: empty looks left out, cascade order. What gets stored
	 * back and what the instance class is hashed from.
	 *
	 * @return array{looks: list<Look>}|\stdClass
	 */
	public function jsonSerialize(): array|\stdClass
	{
		$looks = $this->base->isEmpty() ? $this->looks : $this->looks();

		return [] === $looks ? new \stdClass() : [ 'looks' => $looks ];
	}

	/**
	 * @param list<Look> $looks
	 * @return list<Look>
	 */
	private static function sorted( array $looks ): array
	{
		usort( $looks, static fn ( Look $a, Look $b ): int => LookKey::compare( $a->key, $b->key ) );

		return $looks;
	}
}
