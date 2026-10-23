<?php

declare(strict_types=1);

namespace Fb\ThemeJson;

use Fb\Hookable;

/**
 * Empties core's built-in theme.json layers: the `default` origin (default
 * palette, gradients, font sizes, element styles, 24px block gap…) and the
 * `blocks` origin (default styles declared in each block.json).
 *
 * Done at the data level so every consumer agrees: the PHP frontend
 * stylesheet, the global styles REST endpoint, and the editor, which
 * regenerates global styles CSS in the browser from this same data.
 *
 * Only theme.json (theme origin) and user global styles remain. Core's
 * layout display rules (`.is-layout-flex{display:flex}`…) are not data and
 * survive, which unmanaged core blocks need.
 */
final readonly class CoreDefaultsStripper implements Hookable
{
	/**
	 * Preset lists to empty, as paths under `settings`.
	 */
	private const array PRESET_PATHS = [
		[ 'color', 'palette' ],
		[ 'color', 'gradients' ],
		[ 'color', 'duotone' ],
		[ 'typography', 'fontSizes' ],
		[ 'typography', 'fontFamilies' ],
		[ 'spacing', 'spacingSizes' ],
		[ 'shadow', 'presets' ],
		[ 'dimensions', 'aspectRatios' ],
		[ 'dimensions', 'dimensionSizes' ],
		[ 'border', 'radiusSizes' ],
	];

	public function register(): void
	{
		add_filter( 'wp_theme_json_data_default', $this->strip( ... ) );
		add_filter( 'wp_theme_json_data_blocks', $this->stripBlockStyles( ... ) );
	}

	private function stripBlockStyles( \WP_Theme_JSON_Data $blocks ): \WP_Theme_JSON_Data
	{
		$data           = $blocks->get_data();
		$data['styles'] = [];

		return new \WP_Theme_JSON_Data( $data, 'blocks' );
	}

	private function strip( \WP_Theme_JSON_Data $defaults ): \WP_Theme_JSON_Data
	{
		// Boundary: core's raw theme.json array.
		$data = $defaults->get_data();

		$settings = is_array( $data['settings'] ?? null ) ? $data['settings'] : [];

		foreach ( self::PRESET_PATHS as [ $group, $key ] ) {
			$section         = is_array( $settings[ $group ] ?? null ) ? $settings[ $group ] : [];
			$section[ $key ] = [];
			$settings[ $group ] = $section;
		}

		// Core generates spacing presets from a scale unless told otherwise.
		$spacing                 = is_array( $settings['spacing'] ?? null ) ? $settings['spacing'] : [];
		$spacing['spacingScale'] = [ 'steps' => 0 ];
		$settings['spacing']     = $spacing;

		// Per-block default settings (e.g. core/button presets).
		unset( $settings['blocks'] );

		$data['settings'] = $settings;
		// Core's layout rules reference the root block gap; without a value
		// they'd print empty declarations. 0 = no implicit spacing anywhere.
		$data['styles'] = [ 'spacing' => [ 'blockGap' => '0' ] ];

		return new \WP_Theme_JSON_Data( $data, 'default' );
	}
}
