<?php

declare(strict_types=1);

namespace Fb\Actions;

use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;

/**
 * A block's own actions: its rules, and its anchor (if other blocks' rules
 * point at it). Both live in its `fb` attribute next to its looks, but are
 * not part of its look: they never change its CSS (or its instance class).
 *
 *   {"looks": […], "anchor": "a7k2b9", "actions": [rule…]}
 *
 * Only a block's own attribute holds them: type defaults and variants are
 * reused across pages, while rules point at blocks of this page.
 */
final readonly class BlockActions
{
	/** Rules kept per block. */
	public const int MAX_RULES = 10;

	/**
	 * @param list<ActionRule> $rules
	 */
	public function __construct(
		public ?BlockAnchor $anchor = null,
		public array $rules = [],
	) {
	}

	/**
	 * @param mixed $attribute The block's raw `fb` attribute.
	 */
	public static function fromAttribute( mixed $attribute, ActionCatalog $catalog ): self
	{
		$root = JsonValue::of( $attribute, 'fb' );
		if ( ! $root->isObject() ) {
			return new self();
		}

		$rules = [];
		if ( $root->has( 'actions' ) ) {
			try {
				foreach ( $root->get( 'actions' )->list() as $item ) {
					$rule = ActionRule::parse( $item, $catalog );
					if ( null !== $rule ) {
						$rules[] = $rule;
					}
				}
			} catch ( InvalidJson ) {
				$rules = [];
			}
		}

		return new self( BlockAnchor::ofAttribute( $attribute ), array_slice( $rules, 0, self::MAX_RULES ) );
	}

	public function isEmpty(): bool
	{
		return null === $this->anchor && [] === $this->rules;
	}
}
