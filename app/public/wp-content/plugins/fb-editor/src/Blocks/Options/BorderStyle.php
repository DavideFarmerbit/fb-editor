<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * Line style of a border, all sides (border-*-style).
 */
enum BorderStyle: string implements LabeledOption
{
	case None   = 'none';
	case Solid  = 'solid';
	case Dashed = 'dashed';
	case Dotted = 'dotted';
	case Double = 'double';

	public function label(): string
	{
		return match ( $this ) {
			self::None   => __( 'None', 'fb-editor' ),
			self::Solid  => __( 'Solid', 'fb-editor' ),
			self::Dashed => __( 'Dashed', 'fb-editor' ),
			self::Dotted => __( 'Dotted', 'fb-editor' ),
			self::Double => __( 'Double', 'fb-editor' ),
		};
	}
}
