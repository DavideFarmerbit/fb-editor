<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/** Letter case: values of `text-transform`. */
enum TextTransform: string implements LabeledOption
{
	case None       = 'none';
	case Uppercase  = 'uppercase';
	case Lowercase  = 'lowercase';
	case Capitalize = 'capitalize';

	public function label(): string
	{
		return match ( $this ) {
			self::None       => __( 'None', 'fb-editor' ),
			self::Uppercase  => __( 'Uppercase', 'fb-editor' ),
			self::Lowercase  => __( 'Lowercase', 'fb-editor' ),
			self::Capitalize => __( 'Capitalize', 'fb-editor' ),
		};
	}
}
