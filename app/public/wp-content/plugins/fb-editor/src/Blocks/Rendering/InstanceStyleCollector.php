<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Values\BlockStyles;
use Fb\Blocks\Values\BlockValues;
use Fb\Blocks\Values\InstanceClass;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Css\CssLayer;
use Fb\Styles\Selector;
use Fb\Styles\StyleBinding;
use Fb\Styles\StyleCompiler;

/**
 * Gathers the style bindings of every block rendered on the page, then
 * compiles them in one pass: a single `fb.instances` layer with one media
 * query per breakpoint, instead of a CSS block per instance.
 *
 * Instances with identical values share an instance class and are compiled
 * once.
 */
final class InstanceStyleCollector
{
	/** @var array<string, list<StyleBinding>> Keyed by instance class. */
	private array $bindings = [];

	/** @var array<string, BlockDefinition> Block types rendered, keyed by name. */
	private array $definitions = [];

	private ?StyleCompiler $compiler = null;
	private ?BlockStyles $styles = null;

	/**
	 * Breakpoints come from the theme, which loads after plugins: resolve
	 * them on first use, not at construction.
	 */
	public function __construct(
		private readonly BreakpointConfig $breakpoints,
		private readonly DefaultsRepository $defaults,
	) {
	}

	/**
	 * Registers an instance. Returns whether it produces any CSS, i.e.
	 * whether its root needs the instance class at all.
	 */
	public function add( InstanceClass $instance, BlockDefinition $definition, BlockValues $values ): bool
	{
		$key = $instance->value;

		$this->definitions[ (string) $definition->name ] = $definition;

		if ( ! array_key_exists( $key, $this->bindings ) ) {
			// Conditions read the instance's value, else the type default.
			$bindings = $this->styles()->bindings( $definition, $values, Selector::forClass( $key ), $this->defaults->for( $definition ) );
			// Bindings whose values compile to nothing (e.g. hidden everywhere)
			// don't justify a class.
			$this->bindings[ $key ] = '' === $this->compiler()->compile( CssLayer::Instances, ...$bindings ) ? [] : $bindings;
		}

		return [] !== $this->bindings[ $key ];
	}

	/**
	 * Block types rendered on the page so far (their defaults get printed).
	 *
	 * @return list<BlockDefinition>
	 */
	public function definitions(): array
	{
		return array_values( $this->definitions );
	}

	public function css(): string
	{
		return $this->compiler()->compile( CssLayer::Instances, ...array_merge( ...array_values( $this->bindings ) ) );
	}

	private function compiler(): StyleCompiler
	{
		return $this->compiler ??= new StyleCompiler( $this->breakpoints->breakpoints() );
	}

	private function styles(): BlockStyles
	{
		return $this->styles ??= new BlockStyles( $this->breakpoints->breakpoints() );
	}
}
