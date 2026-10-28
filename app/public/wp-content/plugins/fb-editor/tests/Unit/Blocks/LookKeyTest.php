<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\Looks\LookKey;
use Fb\Blocks\Looks\MediaCondition;
use Fb\Blocks\States\BlockState;
use PHPUnit\Framework\TestCase;

/**
 * Which looks apply together, and in which order they win.
 */
final class LookKeyTest extends TestCase
{
	public function testIdsAreCanonical(): void
	{
		self::assertSame( '', LookKey::base()->id() );
		self::assertSame( 'hover+active', ( new LookKey( [ BlockState::Active, BlockState::Hover, BlockState::Hover ] ) )->id() );
		self::assertSame( '|dark+reduced-motion', ( new LookKey( [], [ MediaCondition::ReducedMotion, MediaCondition::Dark ] ) )->id() );
		self::assertSame( 'hover|touch', ( new LookKey( [ BlockState::Hover ], [ MediaCondition::Touch ] ) )->id() );
	}

	public function testCascadeOrderPutsMoreSpecificLooksLater(): void
	{
		$keys = [
			new LookKey( [ BlockState::Hover, BlockState::Focus ] ),
			new LookKey( [ BlockState::Hover ], [ MediaCondition::Dark ] ),
			new LookKey( [ BlockState::Active ] ),
			new LookKey( [], [ MediaCondition::Dark ] ),
			LookKey::base(),
			new LookKey( [ BlockState::Hover ] ),
		];
		usort( $keys, LookKey::compare( ... ) );

		self::assertSame(
			[ '', '|dark', 'hover', 'hover|dark', 'active', 'hover+focus' ],
			array_map( static fn ( LookKey $key ): string => $key->id(), $keys )
		);
	}

	public function testAppliesWithin(): void
	{
		$hoverInDark = new LookKey( [ BlockState::Hover ], [ MediaCondition::Dark ] );

		self::assertTrue( LookKey::base()->appliesWithin( $hoverInDark ) );
		self::assertTrue( LookKey::ofState( BlockState::Hover )->appliesWithin( $hoverInDark ) );
		self::assertTrue( ( new LookKey( [], [ MediaCondition::Dark ] ) )->appliesWithin( $hoverInDark ) );
		self::assertFalse( LookKey::ofState( BlockState::Focus )->appliesWithin( $hoverInDark ) );
		self::assertFalse( $hoverInDark->appliesWithin( LookKey::ofState( BlockState::Hover ) ) );
	}

	public function testSelectorAndQuery(): void
	{
		$key = new LookKey( [ BlockState::Active, BlockState::Hover ], [ MediaCondition::Touch, MediaCondition::Dark ] );

		self::assertSame( ':hover:active', $key->pseudoClass() );
		self::assertSame( '(hover: none) and (pointer: coarse) and (prefers-color-scheme: dark)', $key->mediaQuery() );
		self::assertNull( LookKey::ofState( BlockState::Hover )->mediaQuery() );
	}
}
