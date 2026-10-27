<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

enum FontStyle: string implements LabeledOption
{
	case Normal = 'normal';
	case Italic = 'italic';

	public function label(): string
	{
		return match ( $this ) {
			self::Normal => __( 'Normal', 'fb-editor' ),
			self::Italic => __( 'Italic', 'fb-editor' ),
		};
	}
}
