<?php

declare(strict_types=1);

namespace Fb\Presets;

/**
 * theme.json preset families we expose as CSS custom properties.
 */
enum PresetKind: string
{
	case Color      = 'color';
	case Gradient   = 'gradient';
	case Spacing    = 'spacing';
	case FontFamily = 'font-family';
	case FontSize   = 'font-size';
	case Shadow     = 'shadow';

	/**
	 * Path under theme.json `settings`.
	 *
	 * @return array{0: string, 1: string}
	 */
	public function settingsPath(): array
	{
		return match ( $this ) {
			self::Color      => [ 'color', 'palette' ],
			self::Gradient   => [ 'color', 'gradients' ],
			self::Spacing    => [ 'spacing', 'spacingSizes' ],
			self::FontFamily => [ 'typography', 'fontFamilies' ],
			self::FontSize   => [ 'typography', 'fontSizes' ],
			self::Shadow     => [ 'shadow', 'presets' ],
		};
	}

	/**
	 * Key holding the value inside each preset entry.
	 */
	public function valueKey(): string
	{
		return match ( $this ) {
			self::Color      => 'color',
			self::Gradient   => 'gradient',
			self::Spacing    => 'size',
			self::FontFamily => 'fontFamily',
			self::FontSize   => 'size',
			self::Shadow     => 'shadow',
		};
	}
}
