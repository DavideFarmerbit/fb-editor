<?php

declare(strict_types=1);

namespace Fb\Blocks\Options;

use Fb\Blocks\Definition\Input\Select\LabeledOption;

/**
 * How a ::before / ::after is laid out. It's inline by default, where width
 * and height do nothing: a box needs one of the others (or a position).
 */
enum PseudoDisplay: string implements LabeledOption
{
	case Inline      = 'inline';
	case InlineBlock = 'inline-block';
	case Block       = 'block';
	case Flex        = 'flex';

	public function label(): string
	{
		return match ( $this ) {
			self::Inline      => __( 'Inline', 'fb-editor' ),
			self::InlineBlock => __( 'Inline block', 'fb-editor' ),
			self::Block       => __( 'Block', 'fb-editor' ),
			self::Flex        => __( 'Flex', 'fb-editor' ),
		};
	}
}
