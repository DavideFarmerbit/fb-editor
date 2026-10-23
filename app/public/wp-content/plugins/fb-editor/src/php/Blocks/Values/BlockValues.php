<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\Control;
use Fb\Blocks\Definition\ControlId;
use Fb\Blocks\Definition\Scope;
use Fb\Breakpoints\BreakpointId;
use Fb\Breakpoints\BreakpointList;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\Responsive;
use Fb\Values\ResponsiveValue;

/**
 * The values of one block instance, parsed from its `fb` attribute through
 * each control's input. Unknown controls and invalid values are dropped.
 *
 * Static-scope controls are held as a base-only Responsive, so everything
 * downstream treats both scopes the same way.
 */
final readonly class BlockValues implements \JsonSerializable
{
	/**
	 * @param array<string, Responsive<ResponsiveValue>> $values Keyed by ControlId value, in control order. Private storage.
	 */
	private function __construct(
		private BlockDefinition $definition,
		private BreakpointList $breakpoints,
		private array $values,
	) {
	}

	/**
	 * @param mixed $attribute The decoded `fb` block attribute.
	 */
	public static function fromAttribute( mixed $attribute, BlockDefinition $definition, BreakpointList $breakpoints ): self
	{
		$json   = JsonValue::of( $attribute, 'fb' );
		$values = [];

		if ( ! $json->isObject() ) {
			return new self( $definition, $breakpoints, [] );
		}

		foreach ( $definition->controls as $control ) {
			if ( ! $json->has( $control->id->value ) ) {
				continue;
			}
			try {
				$value = self::parseControl( $control, $json->get( $control->id->value ), $breakpoints );
			} catch ( InvalidJson ) {
				continue;
			}
			if ( ! $value->isEmpty() ) {
				$values[ $control->id->value ] = $value;
			}
		}

		return new self( $definition, $breakpoints, $values );
	}

	/**
	 * @return Responsive<ResponsiveValue>
	 * @throws InvalidJson
	 */
	private static function parseControl( Control $control, JsonValue $json, BreakpointList $breakpoints ): Responsive
	{
		if ( Scope::Static === $control->scope ) {
			return $json->isNull() ? Responsive::empty() : Responsive::base( $breakpoints, $control->input->parse( $json ) );
		}

		return Responsive::fromJson( $json, $control->input->parse( ... ), $breakpoints );
	}

	/**
	 * @return Responsive<ResponsiveValue>
	 */
	public function get( ControlId $id ): Responsive
	{
		return $this->values[ $id->value ] ?? Responsive::empty();
	}

	public function isEmpty(): bool
	{
		return [] === $this->values;
	}

	/**
	 * Where static-scope values live.
	 */
	public function baseBreakpoint(): BreakpointId
	{
		return $this->breakpoints->base->id;
	}

	public function resolvedAt( BreakpointId $breakpoint ): ResolvedValues
	{
		$resolved = [];
		foreach ( $this->values as $id => $value ) {
			$at = $value->resolve( $breakpoint, $this->breakpoints );
			if ( null !== $at ) {
				$resolved[ $id ] = $at;
			}
		}

		return new ResolvedValues( $resolved );
	}

	/**
	 * Canonical form (control order, sanitized values): what gets stored
	 * back and what the instance class is hashed from.
	 *
	 * @return array<string, mixed>|\stdClass
	 */
	public function jsonSerialize(): array|\stdClass
	{
		if ( [] === $this->values ) {
			return new \stdClass();
		}

		$json = [];
		foreach ( $this->values as $id => $value ) {
			$control     = $this->definition->controls->get( new ControlId( $id ) );
			$json[ $id ] = Scope::Static === $control?->scope
				? $value->at( $this->breakpoints->base->id )
				: $value;
		}

		return $json;
	}
}
