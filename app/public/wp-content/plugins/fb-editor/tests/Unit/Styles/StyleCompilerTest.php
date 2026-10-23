<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Styles;

use Fb\Breakpoints\BreakpointConfig;
use Fb\Breakpoints\BreakpointId;
use Fb\Breakpoints\BreakpointList;
use Fb\Css\CssLayer;
use Fb\Presets\PresetKind;
use Fb\Styles\CssProperty;
use Fb\Styles\Output\AxisOutput;
use Fb\Styles\Output\BoxOutput;
use Fb\Styles\Output\BoxProperty;
use Fb\Styles\Output\PropertyOutput;
use Fb\Styles\Selector;
use Fb\Styles\StyleBinding;
use Fb\Styles\StyleCompiler;
use Fb\Styles\Visibility;
use Fb\Values\AutoLength;
use Fb\Values\BoxValue;
use Fb\Values\CustomLength;
use Fb\Values\KeywordValue;
use Fb\Values\PresetLength;
use Fb\Values\Responsive;
use Fb\Values\Unit;
use PHPUnit\Framework\TestCase;

/**
 * Breakpoints (fallback): desktop (base) > tablet ≤1024 > mobile ≤767.
 */
final class StyleCompilerTest extends TestCase
{
	private BreakpointList $breakpoints;
	private StyleCompiler $compiler;
	private Selector $root;

	protected function setUp(): void
	{
		$this->breakpoints = BreakpointConfig::fallback();
		$this->compiler    = new StyleCompiler( $this->breakpoints );
		$this->root        = Selector::forClass( 'fb-abc1234' );
	}

	private static function px( float $n ): CustomLength
	{
		return new CustomLength( $n, Unit::Px );
	}

	private static function bp( string $id ): BreakpointId
	{
		return new BreakpointId( $id );
	}

	public function testBaseOnly(): void
	{
		$css = $this->compiler->compile(
			CssLayer::Instances,
			new StyleBinding( $this->root, new BoxOutput( BoxProperty::Padding ), Responsive::base( $this->breakpoints, new BoxValue( top: new PresetLength( PresetKind::Spacing, '80' ) ) ) )
		);

		self::assertSame( '@layer fb.instances{:where(.fb-abc1234){padding-top:var(--wp--preset--spacing--80);}}', $css );
	}

	public function testSmallerBreakpointOnlyWritesWhatChanges(): void
	{
		$value = Responsive::base( $this->breakpoints, BoxValue::all( self::px( 80 ) ) )
			// Same as desktop: nothing to write at tablet.
			->with( self::bp( 'tablet' ), BoxValue::all( self::px( 80 ) ) )
			// Only top differs on mobile.
			->with( self::bp( 'mobile' ), new BoxValue( top: self::px( 20 ) ) );

		$css = $this->compiler->compile( CssLayer::Instances, new StyleBinding( $this->root, new BoxOutput( BoxProperty::Padding ), $value ) );

		self::assertSame(
			'@layer fb.instances{'
			. ':where(.fb-abc1234){padding-top:80px;padding-right:80px;padding-bottom:80px;padding-left:80px;}'
			. '@media (max-width: 767px){:where(.fb-abc1234){padding-top:20px;}}'
			. '}',
			$css
		);
	}

	public function testHiddenControlRevertsInheritedValueAndRestatesWhenVisibleAgain(): void
	{
		$gap = new StyleBinding(
			$this->root,
			AxisOutput::gap(),
			Responsive::base( $this->breakpoints, BoxValue::all( self::px( 10 ) ) ),
			Visibility::hiddenAt( self::bp( 'tablet' ) )
		);

		self::assertSame(
			'@layer fb.instances{'
			. ':where(.fb-abc1234){row-gap:10px;column-gap:10px;}'
			. '@media (max-width: 1024px){:where(.fb-abc1234){row-gap:revert-layer;column-gap:revert-layer;}}'
			. '@media (max-width: 767px){:where(.fb-abc1234){row-gap:10px;column-gap:10px;}}'
			. '}',
			$this->compiler->compile( CssLayer::Instances, $gap )
		);
	}

	public function testHiddenWithNothingToInheritWritesNothing(): void
	{
		$css = $this->compiler->compile(
			CssLayer::Instances,
			new StyleBinding(
				$this->root,
				AxisOutput::gap(),
				Responsive::empty()->with( self::bp( 'mobile' ), BoxValue::all( self::px( 4 ) ) ),
				Visibility::hiddenAt( self::bp( 'desktop' ), self::bp( 'tablet' ) )
			)
		);

		self::assertSame( '@layer fb.instances{@media (max-width: 767px){:where(.fb-abc1234){row-gap:4px;column-gap:4px;}}}', $css );
	}

	public function testBindingsOnTheSameSelectorShareOneRule(): void
	{
		$display = new StyleBinding(
			$this->root,
			new PropertyOutput( CssProperty::Display ),
			Responsive::base( $this->breakpoints, new KeywordValue( 'flex' ) )->with( self::bp( 'mobile' ), new KeywordValue( 'block' ) )
		);
		$padding = new StyleBinding(
			$this->root,
			new BoxOutput( BoxProperty::Padding ),
			Responsive::empty()->with( self::bp( 'mobile' ), new BoxValue( left: self::px( 0 ) ) )
		);
		$inner = new StyleBinding(
			$this->root->child( 'fb-section__inner' ),
			new BoxOutput( BoxProperty::Margin ),
			Responsive::base( $this->breakpoints, new BoxValue( right: new AutoLength(), left: new AutoLength() ) )
		);

		self::assertSame(
			'@layer fb.instances{'
			. ':where(.fb-abc1234){display:flex;}'
			. ':where(.fb-abc1234>.fb-section__inner){margin-right:auto;margin-left:auto;}'
			. '@media (max-width: 767px){:where(.fb-abc1234){display:block;padding-left:0;}}'
			. '}',
			$this->compiler->compile( CssLayer::Instances, $display, $padding, $inner )
		);
	}

	public function testNothingToWriteProducesEmptyString(): void
	{
		self::assertSame(
			'',
			$this->compiler->compile( CssLayer::Instances, new StyleBinding( $this->root, new BoxOutput( BoxProperty::Padding ), Responsive::empty() ) )
		);
	}

	public function testSelectorRejectsInjection(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		Selector::forClass( 'x){}body{display:none' );
	}
}
