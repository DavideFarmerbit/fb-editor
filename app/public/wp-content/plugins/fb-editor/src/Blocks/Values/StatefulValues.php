<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\States\BlockState;
use Fb\Breakpoints\BreakpointList;
use Fb\Json\JsonValue;

/**
 * Everything one source styles a block with: its base look plus a look per
 * state. Blocks (their `fb` attribute) and type defaults have this shape:
 *
 *   {"base": {controlId: …}, "states": {"hover": {controlId: …}}}
 *
 * States the block type doesn't support, and controls that can't vary per
 * state, are dropped.
 */
final readonly class StatefulValues implements \JsonSerializable
{
	/**
	 * @param array<string, BlockValues> $states Keyed by BlockState value, supported states only, in cascade order, none empty. Private storage.
	 */
	private function __construct(
		private BlockDefinition $definition,
		public BlockValues $base,
		private array $states,
	) {
	}

	public static function empty( BlockDefinition $definition, BreakpointList $breakpoints ): self
	{
		return new self( $definition, BlockValues::empty( $definition, $breakpoints ), [] );
	}

	/**
	 * @param mixed $json Decoded JSON (a block attribute, a saved default…).
	 */
	public static function fromJson( mixed $json, BlockDefinition $definition, BreakpointList $breakpoints ): self
	{
		$root = JsonValue::of( $json, 'fb' );
		if ( ! $root->isObject() ) {
			return self::empty( $definition, $breakpoints );
		}

		$base       = BlockValues::fromAttribute( $root->has( 'base' ) ? $root->get( 'base' )->decoded() : null, $definition, $breakpoints );
		$statesJson = $root->has( 'states' ) ? $root->get( 'states' ) : null;

		$states = [];
		foreach ( $definition->states as $state ) {
			if ( null === $statesJson || ! $statesJson->isObject() || ! $statesJson->has( $state->value ) ) {
				continue;
			}
			$values = BlockValues::fromAttribute( $statesJson->get( $state->value )->decoded(), $definition, $breakpoints )->onlyStateful();
			if ( ! $values->isEmpty() ) {
				$states[ $state->value ] = $values;
			}
		}

		return new self( $definition, $base, $states );
	}

	/** One state's look; empty for states it doesn't set. */
	public function state( BlockState $state ): BlockValues
	{
		return $this->states[ $state->value ] ?? BlockValues::empty( $this->definition, $this->base->breakpoints() );
	}

	public function isEmpty(): bool
	{
		return $this->base->isEmpty() && [] === $this->states;
	}

	/**
	 * Where values come from for this source in a state (null: the base
	 * look), with weaker sources after it. Any state beats any base:
	 *
	 *   this state → weaker states… → this base → weaker bases…
	 */
	public function chain( ?BlockState $state, self ...$weaker ): ValueChain
	{
		$sources = [];
		if ( null !== $state ) {
			$sources[] = $this->state( $state );
			foreach ( $weaker as $source ) {
				$sources[] = $source->state( $state );
			}
		}
		$sources[] = $this->base;
		foreach ( $weaker as $source ) {
			$sources[] = $source->base;
		}

		return new ValueChain( $this->definition, ...$sources );
	}

	/**
	 * `$over` laid on top, per look, control and breakpoint (saved
	 * defaults over the theme's).
	 */
	public function overlay( self $over ): self
	{
		$states = [];
		foreach ( $this->definition->states as $state ) {
			$merged = $this->state( $state )->overlay( $over->state( $state ) );
			if ( ! $merged->isEmpty() ) {
				$states[ $state->value ] = $merged;
			}
		}

		return new self( $this->definition, $this->base->overlay( $over->base ), $states );
	}

	/**
	 * Canonical form: empty looks left out. What gets stored back and what
	 * the instance class is hashed from.
	 *
	 * @return array{base?: BlockValues, states?: array<string, BlockValues>}|\stdClass
	 */
	public function jsonSerialize(): array|\stdClass
	{
		$json = [];
		if ( ! $this->base->isEmpty() ) {
			$json['base'] = $this->base;
		}
		if ( [] !== $this->states ) {
			$json['states'] = $this->states;
		}

		return [] === $json ? new \stdClass() : $json;
	}
}
