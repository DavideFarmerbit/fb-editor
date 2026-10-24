<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

enum Tag: string implements LabeledOption
{
	case Section = 'section';
	case Div     = 'div';

	public function label(): string
	{
		return "<{$this->value}>";
	}
}
