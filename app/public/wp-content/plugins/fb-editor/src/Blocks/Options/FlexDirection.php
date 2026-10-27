<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * Direction of a flex container's main axis (flex-direction).
 */
enum FlexDirection: string implements LabeledOption
{
	case Row           = 'row';
	case RowReverse    = 'row-reverse';
	case Column        = 'column';
	case ColumnReverse = 'column-reverse';

	public function label(): string
	{
		return match ( $this ) {
			self::Row           => __( 'Row', 'fb-editor' ),
			self::RowReverse    => __( 'Row, reversed', 'fb-editor' ),
			self::Column        => __( 'Column', 'fb-editor' ),
			self::ColumnReverse => __( 'Column, reversed', 'fb-editor' ),
		};
	}
}
