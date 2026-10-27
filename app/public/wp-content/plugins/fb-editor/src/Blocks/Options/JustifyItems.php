<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * Inline alignment of grid items inside their cells (justify-items).
 */
enum JustifyItems: string implements LabeledOption
{
	case Stretch = 'stretch';
	case Start   = 'start';
	case Center  = 'center';
	case End     = 'end';

	public function label(): string
	{
		return match ( $this ) {
			self::Stretch => __( 'Stretch', 'fb-editor' ),
			self::Start   => __( 'Start', 'fb-editor' ),
			self::Center  => __( 'Center', 'fb-editor' ),
			self::End     => __( 'End', 'fb-editor' ),
		};
	}
}
