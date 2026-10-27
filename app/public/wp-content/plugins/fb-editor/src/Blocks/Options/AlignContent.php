<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * Cross-axis distribution of wrapped lines or grid rows (align-content).
 */
enum AlignContent: string implements LabeledOption
{
	case Stretch      = 'stretch';
	case Start        = 'flex-start';
	case Center       = 'center';
	case End          = 'flex-end';
	case SpaceBetween = 'space-between';
	case SpaceAround  = 'space-around';
	case SpaceEvenly  = 'space-evenly';

	public function label(): string
	{
		return match ( $this ) {
			self::Stretch      => __( 'Stretch', 'fb-editor' ),
			self::Start        => __( 'Start', 'fb-editor' ),
			self::Center       => __( 'Center', 'fb-editor' ),
			self::End          => __( 'End', 'fb-editor' ),
			self::SpaceBetween => __( 'Space between', 'fb-editor' ),
			self::SpaceAround  => __( 'Space around', 'fb-editor' ),
			self::SpaceEvenly  => __( 'Space evenly', 'fb-editor' ),
		};
	}
}
