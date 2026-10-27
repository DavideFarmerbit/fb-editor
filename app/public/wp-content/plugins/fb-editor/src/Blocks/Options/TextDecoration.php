<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/** Values of `text-decoration-line`. */
enum TextDecoration: string implements LabeledOption
{
	case None        = 'none';
	case Underline   = 'underline';
	case Overline    = 'overline';
	case LineThrough = 'line-through';

	public function label(): string
	{
		return match ( $this ) {
			self::None        => __( 'None', 'fb-editor' ),
			self::Underline   => __( 'Underline', 'fb-editor' ),
			self::Overline    => __( 'Overline', 'fb-editor' ),
			self::LineThrough => __( 'Strikethrough', 'fb-editor' ),
		};
	}
}
