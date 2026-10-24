<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

enum Display: string implements LabeledOption
{
	case Block = 'block';
	case Flex  = 'flex';
	case Grid  = 'grid';

	public function label(): string
	{
		return ucfirst( $this->value );
	}
}
