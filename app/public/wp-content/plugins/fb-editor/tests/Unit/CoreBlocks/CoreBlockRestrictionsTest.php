<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\CoreBlocks;

use PHPUnit\Framework\TestCase;

/**
 * Checks the restrictions as the running site applies them.
 */
final class CoreBlockRestrictionsTest extends TestCase
{
	public function testAllowedCoreBlocksLoseStylingSupportsAndVariations(): void
	{
		$args = apply_filters(
			'register_block_type_args',
			[
				'supports' => [ 'color' => [ 'text' => true ], 'typography' => [ 'fontSize' => true ], 'layout' => true, 'anchor' => true, 'splitting' => true ],
				'styles'   => [ [ 'name' => 'rounded' ] ],
			],
			'core/paragraph'
		);

		self::assertIsArray( $args );
		self::assertSame( [ 'anchor' => true, 'splitting' => true ], $args['supports'] );
		self::assertSame( [], $args['styles'] );
	}

	public function testOtherBlocksAreUntouched(): void
	{
		$args = [ 'supports' => [ 'color' => true ], 'styles' => [ [ 'name' => 'x' ] ] ];

		self::assertSame( $args, apply_filters( 'register_block_type_args', $args, 'acme/other' ) );
	}

	public function testInserterOffersFbAndAllowedCoreBlocksOnly(): void
	{
		$allowed = get_allowed_block_types( new \WP_Block_Editor_Context( [ 'name' => 'core/edit-post' ] ) );

		self::assertIsArray( $allowed );
		self::assertContains( 'fb/container', $allowed );
		self::assertContains( 'core/paragraph', $allowed );
		self::assertContains( 'core/template-part', $allowed );
		self::assertNotContains( 'core/cover', $allowed );
		self::assertNotContains( 'core/columns', $allowed );
	}

	public function testCoreResponsiveAndStateEditingAreOff(): void
	{
		// Outside a real admin request the asset registries don't exist yet.
		wp_styles();
		wp_scripts();
		$settings = get_block_editor_settings( [], new \WP_Block_Editor_Context( [ 'name' => 'core/edit-post' ] ) );

		self::assertFalse( $settings['responsiveEditingEnabled'] );
		self::assertFalse( $settings['blockStatesEditingEnabled'] );
	}
}
