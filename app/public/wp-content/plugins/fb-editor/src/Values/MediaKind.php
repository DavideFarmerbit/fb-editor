<?php

declare(strict_types=1);

namespace Fb\Values;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Kinds of media library items a media input accepts.
 */
#[ExportToTypeScript]
enum MediaKind: string
{
	case Image = 'image';
	case Video = 'video';
}
