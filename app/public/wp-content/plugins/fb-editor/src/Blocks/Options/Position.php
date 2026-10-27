<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * How a block is positioned (position); offsets apply to all but static.
 */
enum Position: string implements LabeledOption
{
	case Static   = 'static';
	case Relative = 'relative';
	case Absolute = 'absolute';
	case Sticky   = 'sticky';
	case Fixed    = 'fixed';

	public function label(): string
	{
		return match ( $this ) {
			self::Static   => __( 'Static', 'fb-editor' ),
			self::Relative => __( 'Relative', 'fb-editor' ),
			self::Absolute => __( 'Absolute', 'fb-editor' ),
			self::Sticky   => __( 'Sticky', 'fb-editor' ),
			self::Fixed    => __( 'Fixed', 'fb-editor' ),
		};
	}
}
