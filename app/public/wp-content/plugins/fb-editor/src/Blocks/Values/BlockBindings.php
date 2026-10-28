<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\Looks\LookKey;
use Fb\Css\CssLayer;
use Fb\Styles\StyleBinding;
use Fb\Styles\StyleCompiler;

/**
 * Style bindings of one or more blocks, grouped by look. Looks without
 * states go in a base layer, looks with states in its state layer (above
 * every base layer); within each, looks in cascade order (LookKey::compare()),
 * the rules of every block for one look together. A look with conditions is
 * wrapped in their media query.
 */
final readonly class BlockBindings
{
	/**
	 * @param array<string, LookKey>            $keys     Keyed by LookKey id. Private storage.
	 * @param array<string, list<StyleBinding>> $bindings Same keys. Private storage.
	 */
	public function __construct(
		private array $keys = [],
		private array $bindings = [],
	) {
	}

	/** These bindings plus more for one look (after any it already has). */
	public function with( LookKey $key, StyleBinding ...$bindings ): self
	{
		if ( [] === $bindings ) {
			return $this;
		}

		$id = $key->id();

		return new self(
			[ ...$this->keys, $id => $key ],
			[ ...$this->bindings, $id => [ ...( $this->bindings[ $id ] ?? [] ), ...array_values( $bindings ) ] ]
		);
	}

	public function merge( self $other ): self
	{
		$merged = $this;
		foreach ( $other->keys as $id => $key ) {
			$merged = $merged->with( $key, ...( $other->bindings[ $id ] ?? [] ) );
		}

		return $merged;
	}

	/**
	 * @param CssLayer $layer The base layer (defaults, variants or instances).
	 */
	public function css( StyleCompiler $compiler, CssLayer $layer ): string
	{
		$keys = array_values( $this->keys );
		usort( $keys, LookKey::compare( ... ) );

		$base   = '';
		$states = '';
		foreach ( $keys as $key ) {
			$rules = $compiler->rules( ...( $this->bindings[ $key->id() ] ?? [] ) );
			$query = $key->mediaQuery();
			if ( '' !== $rules && null !== $query ) {
				$rules = "@media {$query}{{$rules}}";
			}
			if ( $key->hasStates() ) {
				$states .= $rules;
			} else {
				$base .= $rules;
			}
		}

		return ( '' === $base ? '' : $layer->wrap( $base ) ) . ( '' === $states ? '' : $layer->states()->wrap( $states ) );
	}
}
