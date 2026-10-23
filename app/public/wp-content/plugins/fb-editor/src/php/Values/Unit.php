<?php

declare(strict_types=1);

namespace Fb\Values;

enum Unit: string
{
	case Px      = 'px';
	case Rem     = 'rem';
	case Em      = 'em';
	case Percent = '%';
	case Vw      = 'vw';
	case Vh      = 'vh';
}
