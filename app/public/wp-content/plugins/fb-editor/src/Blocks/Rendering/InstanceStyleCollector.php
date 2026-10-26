<?php

declare(strict_types=1);

namespace Fb\Blocks\Rendering;

use Fb\Blocks\Defaults\DefaultsRepository;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Values\BlockStyles;
use Fb\Blocks\Values\BlockBindings;
use Fb\Blocks\Values\StatefulValues;
use Fb\Blocks\Values\InstanceClass;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Css\CssLayer;
use Fb\Styles\Selector;
use Fb\Styles\StyleCompiler;

/**
 * Gathers the style bindings of every block rendered on the page, then
 * compiles them in one pass: one `fb.instances` layer (plus its state layer)
 * with one media query per breakpoint, instead of a CSS block per instance.
 *
 * Instances with identical values share an instance class and are compiled
 * once.
 */
final class InstanceStyleCollector
{
	/** @var array<string, BlockBindings|null> Keyed by instance class; null: no CSS. */
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
	public function add( InstanceClass $instance, BlockDefinition $definition, StatefulValues $values ): bool
	{
		$key = $instance->value;

		$this->definitions[ (string) $definition->name ] = $definition;

		if ( ! array_key_exists( $key, $this->bindings ) ) {
			// Conditions read the instance's value, else the type default.
			$bindings = $this->styles()->forSource( $definition, $values, Selector::forClass( $key ), $this->defaults->for( $definition ) );
			// Bindings whose values compile to nothing (e.g. hidden everywhere)
			// don't justify a class.
			$this->bindings[ $key ] = '' === $bindings->css( $this->compiler(), CssLayer::Instances ) ? null : $bindings;
		}

		return null !== $this->bindings[ $key ];
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
		$all = new BlockBindings();
		foreach ( $this->bindings as $bindings ) {
			if ( null !== $bindings ) {
				$all = $all->merge( $bindings );
			}
		}

		return $all->css( $this->compiler(), CssLayer::Instances );
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
