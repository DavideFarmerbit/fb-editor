<?php

declare(strict_types=1);

namespace Fb\CoreBlocks;

use Fb\Blocks\BlockRegistry;
use Fb\Hookable;

/**
 * Keeps core blocks inside fb's system:
 * - the inserter offers fb blocks + AllowedCoreBlock only;
 * - allowed core blocks lose core's styling supports and style variations
 *   (server side here; the editor uses core's bundled metadata, so
 *   core-block-restrictions.ts strips the same list in JS);
 * - WordPress 7.1's own responsive / state styling is switched off.
 */
final readonly class CoreBlockRestrictions implements Hookable
{
	public function __construct(
		private BlockRegistry $blocks,
	) {
	}

	public function register(): void
	{
		add_filter( 'allowed_block_types_all', $this->allowedBlocks( ... ) );
		add_filter( 'register_block_type_args', $this->stripSupports( ... ), 10, 2 );
		add_filter( 'block_editor_settings_all', $this->editorSettings( ... ) );
	}

	/**
	 * @return list<string>
	 */
	private function allowedBlocks(): array
	{
		$names = [];
		foreach ( $this->blocks->blocks as $block ) {
			$names[] = (string) $block->definition()->name;
		}
		foreach ( AllowedCoreBlock::cases() as $core ) {
			$names[] = $core->value;
		}

		return $names;
	}

	/**
	 * @param array<mixed> $args Core's registration args (boundary).
	 * @return array<mixed>
	 */
	private function stripSupports( array $args, string $name ): array
	{
		if ( null === AllowedCoreBlock::tryFrom( $name ) ) {
			return $args;
		}

		if ( is_array( $args['supports'] ?? null ) ) {
			foreach ( StrippedSupport::cases() as $support ) {
				unset( $args['supports'][ $support->value ] );
			}
		}
		$args['styles'] = [];

		return $args;
	}

	/**
	 * @param array<mixed> $settings Editor settings (boundary).
	 * @return array<mixed>
	 */
	private function editorSettings( array $settings ): array
	{
		// WordPress 7.1's per-viewport / per-state style editing: fb owns
		// breakpoints, core's version would write styles we don't output.
		$settings['responsiveEditingEnabled']  = false;
		$settings['blockStatesEditingEnabled'] = false;
		// One list per block, no Settings/Styles tabs: fb's sidebar has its
		// own Settings/Styles view filter instead (no switching back and forth).
		$settings['blockInspectorTabs'] = [ 'default' => false ];

		return $settings;
	}
}
