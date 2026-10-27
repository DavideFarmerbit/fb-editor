<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * One item's own cross-axis alignment, overriding its container's (align-self).
 */
enum AlignSelf: string implements LabeledOption
{
	case Auto     = 'auto';
	case Stretch  = 'stretch';
	case Start    = 'flex-start';
	case Center   = 'center';
	case End      = 'flex-end';
	case Baseline = 'baseline';

	public function label(): string
	{
		return match ( $this ) {
			self::Auto     => __( 'Auto', 'fb-editor' ),
			self::Stretch  => __( 'Stretch', 'fb-editor' ),
			self::Start    => __( 'Start', 'fb-editor' ),
			self::Center   => __( 'Center', 'fb-editor' ),
			self::End      => __( 'End', 'fb-editor' ),
			self::Baseline => __( 'Baseline', 'fb-editor' ),
		};
	}
}
