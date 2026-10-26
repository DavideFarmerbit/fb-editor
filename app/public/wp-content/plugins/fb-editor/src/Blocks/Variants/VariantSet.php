<?php

declare(strict_types=1);

namespace Fb\Blocks\Variants;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Values\StatefulValues;
use Fb\Breakpoints\BreakpointList;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * The variants of one block type, in order. Stored as a list:
 *
 *   [{"slug": "primary", "name": "Primary", "values": {"base": …}}]
 *
 * Invalid entries are dropped one by one; a repeated slug keeps the first.
 */
final readonly class VariantSet implements \JsonSerializable
{
	/**
	 * @param list<Variant> $variants Unique slugs.
	 */
	private function __construct(
		public array $variants,
	) {
	}

	public static function empty(): self
	{
		return new self( [] );
	}

	public static function fromJson( mixed $json, BlockDefinition $definition, BreakpointList $breakpoints ): self
	{
		try {
			$items = JsonValue::of( $json, 'variants' )->list();
		} catch ( InvalidJson ) {
			return self::empty();
		}

		$variants = [];
		foreach ( $items as $item ) {
			try {
				$slug = VariantSlug::tryFrom( $item->isObject() && $item->has( 'slug' ) ? $item->get( 'slug' )->decoded() : null );
				$name = trim( $item->optionalString( 'name' ) ?? '' );
			} catch ( InvalidJson ) {
				continue;
			}
			if ( null === $slug || isset( $variants[ $slug->value ] ) ) {
				continue;
			}
			$values                    = StatefulValues::fromJson( $item->has( 'values' ) ? $item->get( 'values' )->decoded() : null, $definition, $breakpoints );
			$variants[ $slug->value ] = new Variant( $slug, '' === $name ? $slug->value : sanitize_text_field( $name ), $values );
		}

		return new self( array_values( $variants ) );
	}

	public function get( VariantSlug $slug ): ?Variant
	{
		foreach ( $this->variants as $variant ) {
			if ( $variant->slug->equals( $slug ) ) {
				return $variant;
			}
		}

		return null;
	}

	/**
	 * `$over` (saved) on top of these (the theme's): a variant in both keeps
	 * the saved name and the saved values laid over the theme's; the rest
	 * are added after, in order.
	 */
	public function overlay( self $over ): self
	{
		$variants = [];
		foreach ( $this->variants as $variant ) {
			$saved      = $over->get( $variant->slug );
			$variants[] = null === $saved ? $variant : new Variant( $variant->slug, $saved->name, $variant->values->overlay( $saved->values ) );
		}
		foreach ( $over->variants as $variant ) {
			if ( null === $this->get( $variant->slug ) ) {
				$variants[] = $variant;
			}
		}

		return new self( $variants );
	}

	/**
	 * @return list<Variant>
	 */
	public function jsonSerialize(): array
	{
		return $this->variants;
	}
}
