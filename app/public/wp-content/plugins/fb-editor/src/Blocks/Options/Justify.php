<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * Main-axis distribution of a flex row (justify-content).
 */
enum Justify: string implements LabeledOption
{
	case Start        = 'flex-start';
	case Center       = 'center';
	case End          = 'flex-end';
	case SpaceBetween = 'space-between';

	public function label(): string
	{
		return match ( $this ) {
			self::Start        => __( 'Start', 'fb-editor' ),
			self::Center       => __( 'Center', 'fb-editor' ),
			self::End          => __( 'End', 'fb-editor' ),
			self::SpaceBetween => __( 'Space between', 'fb-editor' ),
		};
	}
}
