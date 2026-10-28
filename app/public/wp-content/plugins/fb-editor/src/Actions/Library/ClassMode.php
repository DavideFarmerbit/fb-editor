<?php

declare(strict_types=1);

namespace Fb\Actions\Library;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

enum ClassMode: string implements LabeledOption
{
	case Toggle = 'toggle';
	case Add    = 'add';
	case Remove = 'remove';

	public function label(): string
	{
		return match ( $this ) {
			self::Toggle => __( 'Toggle', 'fb-editor' ),
			self::Add    => __( 'Add', 'fb-editor' ),
			self::Remove => __( 'Remove', 'fb-editor' ),
		};
	}
}
