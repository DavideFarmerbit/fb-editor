<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Actions;

use Fb\Actions\ActionCatalog;
use Fb\Actions\ActionsOnPage;
use Fb\Actions\BlockActions;
use Fb\Actions\BlockAnchor;
use Fb\Actions\Library\Library;
use Fb\Actions\TargetHiding;
use Fb\Tests\Fixtures\TestBreakpoints;
use PHPUnit\Framework\TestCase;

/**
 * Stored rules in → sanitized rules, page markup and CSS out.
 */
final class ActionsTest extends TestCase
{
	private ActionCatalog $catalog;

	protected function setUp(): void
	{
		// Rendering goes through the plugin's own services: same test
		// breakpoints as every other test (the plugin resolves them once).
		add_filter( 'fb_breakpoints', TestBreakpoints::list( ... ) );
		$this->catalog = Library::catalog();
	}

	protected function tearDown(): void
	{
		remove_all_filters( 'fb_breakpoints' );
	}

	/**
	 * @param list<mixed> $rules
	 */
	private function stored( array $rules, ?string $anchor = null ): string
	{
		$actions = BlockActions::fromAttribute( [ 'anchor' => $anchor, 'actions' => $rules ], $this->catalog );

		return (string) wp_json_encode( $actions->rules, JSON_UNESCAPED_SLASHES );
	}

	public function testRulesAreSanitizedStepByStep(): void
	{
		self::assertSame(
			'[{"on":[{"type":"click","params":{}}],'
				. '"do":[{"type":"open-close","params":{"mode":"open","target":{"block":"pnl001"}}},{"type":"scroll-to","params":{}}],'
				. '"off":["xs"],"undo":"outside"}]',
			$this->stored(
				[
					[
						'on'   => [ [ 'type' => 'click', 'params' => [ 'junk' => 1 ] ], [ 'type' => 'nope' ] ],    // unknown parameter, unknown trigger: dropped
						'if'   => [ [ 'type' => 'has-class' ] ],                                                  // required class missing: dropped
						'do'   => [
							[ 'type' => 'open-close', 'params' => [ 'mode' => 'open', 'target' => [ 'block' => 'pnl001' ] ] ],
							[ 'type' => 'class', 'params' => [ 'className' => 'Not A Class' ] ],                // invalid required class: step dropped
							[ 'type' => 'scroll-to', 'params' => [ 'target' => [ 'block' => '../evil' ] ] ],   // invalid optional target: unset (self)
						],
						'off'  => [ 'xs' ],
						'undo' => 'outside',
					],
				]
			)
		);
	}

	public function testRulesWithoutTriggerOrEffectAreDropped(): void
	{
		self::assertSame(
			'[]',
			$this->stored(
				[
					[ 'on' => [ [ 'type' => 'nope' ] ], 'do' => [ [ 'type' => 'open-close' ] ] ],
					[ 'on' => [ [ 'type' => 'click' ] ], 'do' => [ [ 'type' => 'link', 'params' => [ 'link' => [ 'url' => 'javascript:alert(1)' ] ] ] ] ],
					'garbage',
				]
			)
		);
	}

	public function testAnchorIsKeptOnlyWhenValid(): void
	{
		self::assertSame( 'fb-a-pnl001', BlockActions::fromAttribute( [ 'anchor' => 'pnl001' ], $this->catalog )->anchor?->className() );
		self::assertNull( BlockActions::fromAttribute( [ 'anchor' => 'x"><script>' ], $this->catalog )->anchor );
	}

	public function testRenderingPutsRulesOnTheRootAndTheLinkInsideIt(): void
	{
		$html = do_blocks(
			'<!-- wp:fb/container ' . wp_json_encode(
				[
					'fb' => [
						'anchor'  => 'card01',
						'actions' => [
							[ 'on' => [ [ 'type' => 'click' ] ], 'do' => [ [ 'type' => 'link', 'params' => [ 'link' => [ 'url' => 'https://example.test/?a=1&b=2' ] ] ] ] ],
							[ 'on' => [ [ 'type' => 'hover' ] ], 'do' => [ [ 'type' => 'class', 'params' => [ 'className' => 'is-lit', 'mode' => 'add' ] ] ], 'undo' => 'leave' ],
						],
					],
				]
			) . ' --><!-- wp:paragraph --><p>Read <b>more</b></p><!-- /wp:paragraph --><!-- /wp:fb/container -->'
		);

		self::assertSame(
			'<div data-fb-actions="[{&quot;on&quot;:[{&quot;type&quot;:&quot;hover&quot;,&quot;params&quot;:{}}],&quot;do&quot;:[{&quot;type&quot;:&quot;class&quot;,&quot;params&quot;:{&quot;mode&quot;:&quot;add&quot;,&quot;className&quot;:&quot;is-lit&quot;}}],&quot;undo&quot;:&quot;leave&quot;}]" class="fb-container fb-a-card01">'
				// The link isn't for the script: markup only, named after the block's text.
				. '<a class="fb-link" href="https://example.test/?a=1&#038;b=2" aria-label="Read more"></a>'
				. '<p class="fb-paragraph">Read <b>more</b></p></div>',
			$html
		);
	}

	public function testClosedTargetsAndLinksGetTheirCss(): void
	{
		$page = new ActionsOnPage();
		self::assertSame( '', $page->css() );

		$page->hideUntilOpen( new BlockAnchor( 'pnl001' ), TargetHiding::Removed );
		$page->hideUntilOpen( new BlockAnchor( 'pnl002' ), TargetHiding::KeepsLayout );
		// Asked both ways: removed wins.
		$page->hideUntilOpen( new BlockAnchor( 'pnl001' ), TargetHiding::KeepsLayout );
		$page->hasLink();

		self::assertSame(
			'@layer fb.base{:where(:has(>.fb-link)){position:relative;}:where(.fb-link){position:absolute;top:0;right:0;bottom:0;left:0;z-index:1;}'
				. ':where(:has(>.fb-link) :is(a,button):not(.fb-link)){position:relative;z-index:2;}}'
				. '@layer fb.visibility{:where(.fb-a-pnl001:not(.is-fb-open,[open])){display:none;}:where(.fb-a-pnl002:not(.is-fb-open,[open])){visibility:hidden;}}',
			$page->css()
		);
	}
}
