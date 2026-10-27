<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * How media fills its box (object-fit).
 */
enum ObjectFit: string implements LabeledOption
{
	case Cover   = 'cover';
	case Contain = 'contain';

	public function label(): string
	{
		return match ( $this ) {
			self::Cover   => __( 'Cover (crop)', 'fb-editor' ),
			self::Contain => __( 'Contain (whole)', 'fb-editor' ),
		};
	}
}
