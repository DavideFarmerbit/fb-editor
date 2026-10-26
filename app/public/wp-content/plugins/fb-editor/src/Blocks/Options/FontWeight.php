<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

enum FontWeight: string implements LabeledOption
{
	case Light     = '300';
	case Regular   = '400';
	case Medium    = '500';
	case Semibold  = '600';
	case Bold      = '700';
	case Extrabold = '800';

	public function label(): string
	{
		return match ( $this ) {
			self::Light     => __( 'Light', 'fb-editor' ),
			self::Regular   => __( 'Regular', 'fb-editor' ),
			self::Medium    => __( 'Medium', 'fb-editor' ),
			self::Semibold  => __( 'Semibold', 'fb-editor' ),
			self::Bold      => __( 'Bold', 'fb-editor' ),
			self::Extrabold => __( 'Extra bold', 'fb-editor' ),
		};
	}
}
