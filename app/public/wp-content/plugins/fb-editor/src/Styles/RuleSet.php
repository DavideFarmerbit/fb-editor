<?php

declare(strict_types=1);

namespace Fb\Styles;

/**
 * Rules for one breakpoint: selector → declarations, in first-seen order.
 * Declarations for the same selector are merged into a single rule.
 */
final class RuleSet
{
	/** @var array<string, DeclarationSet> Keyed by rendered selector. */
	private array $rules = [];

	public function add( Selector $selector, DeclarationSet $declarations ): void
	{
		if ( 0 === count( $declarations ) ) {
			return;
		}

		$key                 = (string) $selector;
		$this->rules[ $key ] = ( $this->rules[ $key ] ?? DeclarationSet::empty() )->merge( $declarations );
	}

	public function isEmpty(): bool
	{
		return [] === $this->rules;
	}

	public function toCss(): string
	{
		$css = '';
		foreach ( $this->rules as $selector => $declarations ) {
			$css .= "{$selector}{{$declarations->toCss()}}";
		}

		return $css;
	}
}
