<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Library\Container\Container;
use Fb\Blocks\BlockExtensions;
use Fb\Blocks\Library\Container\ContainerItem;
use Fb\Blocks\Values\StatefulValues;
use Fb\Breakpoints\BreakpointConfig;
use Fb\CoreBlocks\Library\Paragraph\Paragraph;
use Fb\Tests\Fixtures\TestBreakpoints;
use Fb\Tests\Fixtures\TestRendering;
use PHPUnit\Framework\TestCase;

/**
 * Every block gets Container item settings: they style the block itself,
 * apply only inside a Container, and never go into defaults or variants.
 */
final class ContainerItemTest extends TestCase
{
	protected function setUp(): void
	{
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
	}

	protected function tearDown(): void
	{
		remove_all_filters( 'fb_breakpoints' );
	}

	public function testEveryBlockGetsTheItemControlsInTheirOwnPanel(): void
	{
		$definition = BlockExtensions::complete( ( new Paragraph() )->definition() );

		$grow = $definition->controls->get( new ControlId( ContainerItem::GROW ) );
		self::assertNotNull( $grow );
		self::assertTrue( $grow->isPlaced() );
		self::assertFalse( $grow->isStateful() );
		self::assertSame(
			'{"parent":"fb/container","when":{"kind":"in","control":"display","values":["flex"]}}',
			wp_json_encode( $grow->placement, JSON_UNESCAPED_SLASHES )
		);
		self::assertSame( BlockExtensions::complete( ( new Paragraph() )->definition() )->controls->get( new ControlId( ContainerItem::ORDER ) )?->label, 'Order' );
	}

	public function testItemSettingsStyleTheBlockItself(): void
	{
		$rendering = new TestRendering( new BreakpointConfig() );
		$html      = $rendering->render(
			new Container(),
			[
				'fb' => [
					'base' => [
						'itemGrow'       => [ 'desktop' => 1, 'mobile' => 0 ],
						'itemBasis'      => [ 'desktop' => [ 'kind' => 'custom', 'value' => 30, 'unit' => '%' ] ],
						'itemColumnSpan' => [ 'desktop' => 2, 'tablet' => 2.5 ],
						'itemOrder'      => [ 'desktop' => -1 ],
					],
				],
			],
			''
		);
		self::assertSame( 1, preg_match( '#fb-container (fb-[0-9a-z]{7})#', $html, $match ) );
		$instance = $match[1] ?? '';

		// 2.5 isn't a whole span: dropped. Grow stays as stored (1, 0).
		self::assertSame(
			"@layer fb.instances{:where(.{$instance}){flex-grow:1;flex-basis:30%;grid-column:span 2;order:-1;}"
			. "@media (max-width: 767px){:where(.{$instance}){flex-grow:0;}}}",
			$rendering->collector->css()
		);
	}

	public function testDefaultsAndVariantsNeverHoldItemSettings(): void
	{
		$definition = BlockExtensions::complete( ( new Paragraph() )->definition() );
		$json       = [ 'base' => [ 'itemGrow' => [ 'desktop' => 1 ], 'textAlign' => [ 'desktop' => 'center' ] ] ];

		self::assertSame( '{"base":{"textAlign":{"desktop":"center"},"itemGrow":{"desktop":1.0}}}', wp_json_encode( StatefulValues::fromJson( $json, $definition, TestBreakpoints::list() ), JSON_PRESERVE_ZERO_FRACTION ) );
		self::assertSame( '{"base":{"textAlign":{"desktop":"center"}}}', wp_json_encode( StatefulValues::fromJson( $json, $definition, TestBreakpoints::list(), placed: false ) ) );
	}
}
