<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * Cross-axis alignment of a flex or grid container's items (align-items).
 * The flex-* keywords act as start/end in a grid too.
 */
enum AlignItems: string implements LabeledOption
{
	case Stretch  = 'stretch';
	case Start    = 'flex-start';
	case Center   = 'center';
	case End      = 'flex-end';
	case Baseline = 'baseline';

	public function label(): string
	{
		return match ( $this ) {
			self::Stretch  => __( 'Stretch', 'fb-editor' ),
			self::Start    => __( 'Start', 'fb-editor' ),
			self::Center   => __( 'Center', 'fb-editor' ),
			self::End      => __( 'End', 'fb-editor' ),
			self::Baseline => __( 'Baseline', 'fb-editor' ),
		};
	}
}
