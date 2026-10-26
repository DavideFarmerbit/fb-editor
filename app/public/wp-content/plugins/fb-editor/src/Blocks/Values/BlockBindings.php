<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\States\BlockState;
use Fb\Css\CssLayer;
use Fb\Styles\StyleBinding;
use Fb\Styles\StyleCompiler;

/**
 * Style bindings of one or more blocks, split by look: the base look goes in
 * a base layer, states in its state layer (above every base layer), one
 * state after the other in cascade order.
 */
final readonly class BlockBindings
{
	/**
	 * @param list<StyleBinding>               $base
	 * @param array<string, list<StyleBinding>> $states Keyed by BlockState value. Private storage.
	 */
	public function __construct(
		private array $base = [],
		private array $states = [],
	) {
	}

	public function merge( self $other ): self
	{
		$states = $this->states;
		foreach ( $other->states as $state => $bindings ) {
			$states[ $state ] = [ ...( $states[ $state ] ?? [] ), ...$bindings ];
		}

		return new self( [ ...$this->base, ...$other->base ], $states );
	}

	/**
	 * @param CssLayer $layer The base layer (defaults or instances).
	 */
	public function css( StyleCompiler $compiler, CssLayer $layer ): string
	{
		$states = '';
		foreach ( BlockState::cases() as $state ) {
			$states .= $compiler->rules( ...( $this->states[ $state->value ] ?? [] ) );
		}

		return $compiler->compile( $layer, ...$this->base ) . ( '' === $states ? '' : $layer->states()->wrap( $states ) );
	}
}
