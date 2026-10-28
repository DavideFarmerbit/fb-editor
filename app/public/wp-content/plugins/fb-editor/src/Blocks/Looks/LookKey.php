<?php

declare(strict_types=1);

namespace Fb\Blocks\Looks;

use Fb\Blocks\Parts\PartId;
use Fb\Blocks\States\BlockState;

/**
 * Which look of a block: the part it styles (none: the block itself), the
 * states it requires (all at once) and the media conditions it requires
 * (all at once). No part, states or conditions: the base look.
 *
 *   hover          → while hovered
 *   hover + focus  → while hovered and focused
 *   dark           → in dark mode
 *   hover | dark   → while hovered, in dark mode
 *
 * Looks apply together: a hovered button in dark mode gets its base, dark,
 * hover and hover|dark looks, later ones winning (see compare()).
 */
final readonly class LookKey implements \JsonSerializable
{
	/** @var list<BlockState> In cascade order, no duplicates. */
	public array $states;

	/** @var list<MediaCondition> In cascade order, no duplicates. */
	public array $conditions;

	/**
	 * @param list<BlockState>     $states
	 * @param list<MediaCondition> $conditions
	 * @param PartId|null          $part       The part it styles; null: the block.
	 */
	public function __construct( array $states = [], array $conditions = [], public ?PartId $part = null )
	{
		$this->states     = BlockState::ordered( $states );
		$this->conditions = MediaCondition::ordered( $conditions );
	}

	public static function base(): self
	{
		return new self();
	}

	public static function ofState( BlockState $state ): self
	{
		return new self( [ $state ] );
	}

	public function isBase(): bool
	{
		return null === $this->part && [] === $this->states && [] === $this->conditions;
	}

	/** Its CSS goes in a states layer (any state beats any base look). */
	public function hasStates(): bool
	{
		return [] !== $this->states;
	}

	/**
	 * Whether this look applies whenever `$other` does: same part, its states
	 * and conditions among `$other`'s. A part's own base applies within
	 * every look of that part.
	 */
	public function appliesWithin( self $other ): bool
	{
		return $this->part?->value === $other->part?->value
			&& [] === array_diff( self::values( $this->states ), self::values( $other->states ) )
			&& [] === array_diff( self::values( $this->conditions ), self::values( $other->conditions ) );
	}

	public function equals( self $other ): bool
	{
		return $this->id() === $other->id();
	}

	/** Canonical id, e.g. "hover+focus|dark", "before/hover"; "" for the base. */
	public function id(): string
	{
		$states     = implode( '+', self::values( $this->states ) );
		$conditions = implode( '+', self::values( $this->conditions ) );
		$look       = [] === $this->conditions ? $states : "{$states}|{$conditions}";

		return null === $this->part ? $look : "{$this->part}/{$look}";
	}

	/**
	 * Cascade order, weakest first: the block before its parts (by part id),
	 * then fewer states first, then by state; then fewer conditions first,
	 * then by condition. More specific looks come later and win, e.g. hover
	 * < active < hover+focus, and hover < hover|dark.
	 */
	public static function compare( self $a, self $b ): int
	{
		return [ $a->part->value ?? '', count( $a->states ), self::indexes( $a->states, BlockState::cases() ), count( $a->conditions ), self::indexes( $a->conditions, MediaCondition::cases() ) ]
			<=> [ $b->part->value ?? '', count( $b->states ), self::indexes( $b->states, BlockState::cases() ), count( $b->conditions ), self::indexes( $b->conditions, MediaCondition::cases() ) ];
	}

	/** Appended to the root's selector: every state's pseudo-class. */
	public function pseudoClass(): string
	{
		return implode( '', array_map( static fn ( BlockState $state ): string => $state->pseudoClass(), $this->states ) );
	}

	/** The media query its rules are wrapped in; null without conditions. */
	public function mediaQuery(): ?string
	{
		return [] === $this->conditions
			? null
			: implode( ' and ', array_map( static fn ( MediaCondition $condition ): string => $condition->mediaQuery(), $this->conditions ) );
	}

	/**
	 * @return array{part?: PartId, states?: list<BlockState>, when?: list<MediaCondition>}
	 */
	public function jsonSerialize(): array
	{
		$json = [];
		if ( null !== $this->part ) {
			$json['part'] = $this->part;
		}
		if ( [] !== $this->states ) {
			$json['states'] = $this->states;
		}
		if ( [] !== $this->conditions ) {
			$json['when'] = $this->conditions;
		}

		return $json;
	}

	/**
	 * @param list<BlockState|MediaCondition> $cases
	 * @return list<string>
	 */
	private static function values( array $cases ): array
	{
		return array_map( static fn ( BlockState|MediaCondition $case ): string => $case->value, $cases );
	}

	/**
	 * @param list<BlockState|MediaCondition> $cases
	 * @param list<BlockState|MediaCondition> $order
	 * @return list<int>
	 */
	private static function indexes( array $cases, array $order ): array
	{
		return array_map( static fn ( BlockState|MediaCondition $case ): int => (int) array_search( $case, $order, true ), $cases );
	}
}
