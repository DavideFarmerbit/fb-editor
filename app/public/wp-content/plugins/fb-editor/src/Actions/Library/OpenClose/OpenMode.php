<?php

declare(strict_types=1);

namespace Fb\Actions\Library\OpenClose;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

enum OpenMode: string implements LabeledOption
{
	case Toggle = 'toggle';
	case Open   = 'open';
	case Close  = 'close';

	public function label(): string
	{
		return match ( $this ) {
			self::Toggle => __( 'Toggle', 'fb-editor' ),
			self::Open   => __( 'Open', 'fb-editor' ),
			self::Close  => __( 'Close', 'fb-editor' ),
		};
	}
}
