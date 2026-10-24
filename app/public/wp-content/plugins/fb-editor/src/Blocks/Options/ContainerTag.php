<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * HTML elements a container block may render as.
 */
enum ContainerTag: string implements LabeledOption
{
	case Section = 'section';
	case Div     = 'div';
	case Header  = 'header';
	case Footer  = 'footer';
	case Main    = 'main';
	case Article = 'article';
	case Aside   = 'aside';

	public function label(): string
	{
		return "<{$this->value}>";
	}
}
