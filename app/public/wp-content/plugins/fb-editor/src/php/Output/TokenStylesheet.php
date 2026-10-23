<?php

declare(strict_types=1);

namespace Fb\Output;

use Fb\Css\CssLayer;
use Fb\Presets\PresetRepository;

/**
 * fb's base stylesheet: layer order, core layout rules (demoted), tokens,
 * and the user's global-styles custom CSS (unlayered: it's a site override).
 */
final readonly class TokenStylesheet
{
	public function __construct(
		private PresetRepository $presets,
	) {
	}

	/**
	 * @param bool $withCoreStyles Include core's global styles (after
	 *     CoreDefaultsStripper that's only layout rules). The editor canvas
	 *     generates these itself in the browser, so it passes false.
	 */
	public function css( bool $withCoreStyles ): string
	{
		$css = CssLayer::orderStatement();

		if ( $withCoreStyles ) {
			$core = trim( wp_get_global_stylesheet( [ 'styles' ] ) );
			if ( '' !== $core ) {
				$css .= CssLayer::Wp->wrap( $core );
			}
		}

		$declarations = '';
		foreach ( $this->presets->all() as $preset ) {
			$declarations .= "{$preset->cssVariable()}:{$preset->value};";
		}
		if ( '' !== $declarations ) {
			$css .= CssLayer::Tokens->wrap( ":root{{$declarations}}" );
		}

		if ( $withCoreStyles ) {
			$css .= trim( wp_get_global_stylesheet( [ 'custom-css' ] ) );
		}

		return $css;
	}
}
