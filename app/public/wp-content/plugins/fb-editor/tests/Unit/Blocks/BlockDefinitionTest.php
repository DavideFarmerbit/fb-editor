<?php

declare(strict_types=1);

namespace Fb\Tests\Unit\Blocks;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Definition\Condition\Equals;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\ControlSet;
use Fb\Blocks\Definition\ElementId;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Spacing\SpacingInput;
use Fb\Blocks\Definition\Layout;
use Fb\Blocks\Definition\Panel;
use Fb\Styles\Output\BoxOutput;
use Fb\Styles\Output\BoxProperty;
use Fb\Tests\Fixtures\Display;
use Fb\Tests\Fixtures\SectionFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BlockDefinitionTest extends TestCase
{
	public function testFixtureIsValid(): void
	{
		self::assertSame( 'fb-section', SectionFixture::definition()->name->cssClass() );
	}

	/**
	 * @return iterable<string, array{\Closure(): BlockDefinition, string}>
	 */
	public static function invalidDefinitions(): iterable
	{
		$a       = new ControlId( 'a' );
		$b       = new ControlId( 'b' );
		$padding = static fn ( ControlId $id, ?ElementId $element = null, ?Equals $when = null ): Control
			=> new Control( $id, 'X', SpacingInput::padding(), new BoxOutput( BoxProperty::Padding ), $element, visibleWhen: $when );
		$display = new Control( $b, 'Display', new SelectInput( Display::class ) );
		$define  = static fn ( ControlSet $controls, Layout $layout ): BlockDefinition
			=> new BlockDefinition( new BlockName( 'test' ), 'Test', $controls, $layout );

		yield 'control not in any panel' => [
			static fn () => $define( new ControlSet( $padding( $a ), $display ), new Layout( new Panel( 'P', $a ) ) ),
			'not placed in any panel',
		];
		yield 'panel references unknown control' => [
			static fn () => $define( new ControlSet( $padding( $a ) ), new Layout( new Panel( 'P', $a, new ControlId( 'nope' ) ) ) ),
			'unknown control "nope"',
		];
		yield 'control in two panels' => [
			static fn () => $define( new ControlSet( $padding( $a ) ), new Layout( new Panel( 'P', $a ), new Panel( 'Q', $a ) ) ),
			'more than one panel',
		];
		yield 'undeclared element' => [
			static fn () => $define( new ControlSet( $padding( $a, new ElementId( 'inner' ) ) ), new Layout( new Panel( 'P', $a ) ) ),
			'undeclared element "inner"',
		];
		yield 'condition on unknown control' => [
			static fn () => $define( new ControlSet( $padding( $a, null, new Equals( new ControlId( 'ghost' ), Display::Flex ) ) ), new Layout( new Panel( 'P', $a ) ) ),
			'unknown control "ghost"',
		];
		yield 'condition on itself' => [
			static fn () => $define( new ControlSet( $padding( $a, null, new Equals( $a, Display::Flex ) ) ), new Layout( new Panel( 'P', $a ) ) ),
			'references itself',
		];
		yield 'duplicate control id' => [
			static fn () => $define( new ControlSet( $padding( $a ), $padding( $a ) ), new Layout( new Panel( 'P', $a ) ) ),
			'Duplicate control id "a"',
		];
	}

	/**
	 * @param \Closure(): BlockDefinition $build
	 */
	#[DataProvider( 'invalidDefinitions' )]
	public function testInvalidDefinitionsFailAtLoadTime( \Closure $build, string $message ): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( $message );
		$build();
	}

	public function testExportsDefinitionForTheEditor(): void
	{
		$definition = SectionFixture::definition();
		$encode     = static fn ( mixed $value ): string => (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES );

		self::assertStringStartsWith( '{"name":"fb/section","title":"Section","isCore":false,"className":"fb-section",', $encode( $definition ) );
		self::assertStringEndsWith( ',"elements":[{"id":"inner","className":"fb-section__inner","editorClassName":"fb-section__inner"}],'
			. '"states":[{"id":"hover","label":"Hover","pseudoClass":":hover","editorPseudoClass":":is(:hover,.fb-preview-hover)","previewClass":"fb-preview-hover"},'
			. '{"id":"active","label":"Active","pseudoClass":":active","editorPseudoClass":":is(:active,.fb-preview-active)","previewClass":"fb-preview-active"}]}', $encode( $definition ) );
		self::assertSame(
			'{"id":"gap","label":"Gap",'
			. '"input":{"kind":"spacing","allowAuto":false,"allowNegative":false,"modes":["linked","axis"]},'
			. '"output":{"kind":"axis","block":"row-gap","inline":"column-gap"},'
			. '"element":"inner","scope":"responsive",'
			. '"visibleWhen":{"kind":"in","control":"display","values":["flex","grid"]},"placement":null}',
			$encode( $definition->controls->get( new ControlId( 'gap' ) ) )
		);
		self::assertSame(
			'{"kind":"select","options":[{"value":"section","label":"<section>"},{"value":"div","label":"<div>"}],"fallback":null}',
			$encode( $definition->controls->get( new ControlId( 'tag' ) )?->input )
		);
		self::assertSame(
			'[{"label":"Layout","controls":["tag","display","gap"]},{"label":"Spacing","controls":["padding"]}]',
			$encode( $definition->layout )
		);
	}
}
