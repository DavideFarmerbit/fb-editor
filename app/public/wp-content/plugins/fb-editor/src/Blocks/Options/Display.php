<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

enum Display: string implements LabeledOption
{
	case Block = 'block';
	case Flex  = 'flex';
	case Grid  = 'grid';

	public function label(): string
	{
		return match ( $this ) {
			self::Block => __( 'Block', 'fb-editor' ),
			self::Flex  => __( 'Flex', 'fb-editor' ),
			self::Grid  => __( 'Grid', 'fb-editor' ),
		};
	}
}
