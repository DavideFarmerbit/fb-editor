<?php

declare(strict_types=1);

namespace Fb\Blocks\Values;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Variants\VariantSlug;
use Fb\Breakpoints\BreakpointList;
use Fb\Json\JsonValue;

/**
 * A block's whole `fb` attribute: the variant it uses, if any, and its own
 * values on top:
 *
 *   {"variant": "primary", "looks": […]}
 *
 * The variant is kept even if it doesn't exist (anymore): the block then
 * just has no variant, until one with that slug exists again.
 */
final readonly class BlockAttribute implements \JsonSerializable
{
	public function __construct(
		public ?VariantSlug $variant,
		public StatefulValues $values,
	) {
	}

	public static function fromJson( mixed $json, BlockDefinition $definition, BreakpointList $breakpoints ): self
	{
		$root    = JsonValue::of( $json, 'fb' );
		$variant = $root->isObject() && $root->has( 'variant' ) ? VariantSlug::tryFrom( $root->get( 'variant' )->decoded() ) : null;

		return new self( $variant, StatefulValues::fromJson( $json, $definition, $breakpoints ) );
	}

	/**
	 * Canonical form: what gets stored back and what the instance class is
	 * hashed from (the variant changes what conditions see, so it counts).
	 *
	 * @return array<string, mixed>|\stdClass
	 */
	public function jsonSerialize(): array|\stdClass
	{
		$values = $this->values->jsonSerialize();
		if ( null === $this->variant ) {
			return $values;
		}

		return [ 'variant' => $this->variant, ...( $values instanceof \stdClass ? [] : $values ) ];
	}
}
