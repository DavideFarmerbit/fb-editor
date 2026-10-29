<?php

declare(strict_types=1);

namespace Fb\Actions;

use Fb\Blocks\Definition\Input\Breakpoints\BreakpointsInput;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\BreakpointSet;

/**
 * One rule of a block: when any trigger fires and every condition holds,
 * the effects run in order.
 *
 *   {"on": [step…], "if": [step…], "do": [step…], "off": ["xs"], "undo": "outside"}
 *
 * Dropped when parsed: invalid steps one by one, then the rule itself if it
 * has no trigger or no effect left.
 */
final readonly class ActionRule implements \JsonSerializable
{
	/** Steps of each kind kept per rule: more is a sign of a mistake. */
	#[ExportToTypeScript]
	public const int MAX_STEPS = 6;

	/**
	 * @param non-empty-list<Step> $triggers
	 * @param list<Step>           $conditions
	 * @param non-empty-list<Step> $effects
	 */
	public function __construct(
		public array $triggers,
		public array $conditions,
		public array $effects,
		/** Breakpoints where it does nothing; null: none. */
		public ?BreakpointSet $off = null,
		public Undo $undo = Undo::Never,
	) {
	}

	public static function parse( JsonValue $json, ActionCatalog $catalog ): ?self
	{
		if ( ! $json->isObject() ) {
			return null;
		}

		$triggers = self::steps( $json, 'on', ActionKind::Trigger, $catalog );
		$effects  = self::steps( $json, 'do', ActionKind::Effect, $catalog );
		if ( [] === $triggers || [] === $effects ) {
			return null;
		}

		$off = null;
		if ( $json->has( 'off' ) ) {
			try {
				$off = ( new BreakpointsInput() )->parse( $json->get( 'off' ) );
			} catch ( InvalidJson ) {
				$off = null;
			}
		}

		$undo = Undo::Never;
		if ( $json->has( 'undo' ) ) {
			try {
				$undo = Undo::tryFrom( $json->get( 'undo' )->string() ) ?? Undo::Never;
			} catch ( InvalidJson ) {
				$undo = Undo::Never;
			}
		}

		return new self( $triggers, self::steps( $json, 'if', ActionKind::Condition, $catalog ), $effects, null === $off || [] === $off->breakpoints ? null : $off, $undo );
	}

	/**
	 * @return list<Step>
	 */
	private static function steps( JsonValue $json, string $key, ActionKind $kind, ActionCatalog $catalog ): array
	{
		if ( ! $json->has( $key ) ) {
			return [];
		}

		try {
			$items = $json->get( $key )->list();
		} catch ( InvalidJson ) {
			return [];
		}

		$steps = [];
		foreach ( $items as $item ) {
			$step = Step::parse( $item, $kind, $catalog );
			if ( null !== $step ) {
				$steps[] = $step;
			}
		}

		return array_slice( $steps, 0, self::MAX_STEPS );
	}

	/**
	 * The rule as the page script gets it: without the effects done by
	 * markup alone (a link); null when none is left for the script.
	 */
	public function forPage(): ?self
	{
		$effects = array_values(
			array_filter( $this->effects, static fn ( Step $step ): bool => ! $step->type instanceof Effect || $step->type->runsOnPage() )
		);

		return [] === $effects ? null : new self( $this->triggers, $this->conditions, $effects, $this->off, $this->undo );
	}

	/**
	 * Canonical form: what's stored back and what the page receives.
	 *
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array
	{
		$json = [ 'on' => $this->triggers ];
		if ( [] !== $this->conditions ) {
			$json['if'] = $this->conditions;
		}
		$json['do'] = $this->effects;
		if ( null !== $this->off ) {
			$json['off'] = $this->off;
		}
		if ( Undo::Never !== $this->undo ) {
			$json['undo'] = $this->undo;
		}

		return $json;
	}
}
