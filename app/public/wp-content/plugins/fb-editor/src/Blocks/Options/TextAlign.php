<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

enum TextAlign: string implements LabeledOption
{
	case Left    = 'left';
	case Center  = 'center';
	case Right   = 'right';
	case Justify = 'justify';

	public function label(): string
	{
		return match ( $this ) {
			self::Left    => __( 'Left', 'fb-editor' ),
			self::Center  => __( 'Center', 'fb-editor' ),
			self::Right   => __( 'Right', 'fb-editor' ),
			self::Justify => __( 'Justify', 'fb-editor' ),
		};
	}
}
