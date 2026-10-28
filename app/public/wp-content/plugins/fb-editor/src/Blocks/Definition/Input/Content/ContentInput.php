<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Content;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\JsonValue;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\TextValue;

/**
 * What a ::before / ::after shows (CSS `content`): text or symbols. Empty
 * is a value too: an empty box, drawn by its size, color and border.
 * Control characters are dropped; written out by ContentOutput.
 */
final readonly class ContentInput implements Input
{
	#[ExportToTypeScript]
	public const int MAX_LENGTH = 100;

	public function parse( JsonValue $json ): TextValue
	{
		$text = (string) preg_replace( '/[\x00-\x1F\x7F]/u', '', $json->string() );

		return new TextValue( mb_substr( $text, 0, self::MAX_LENGTH ) );
	}

	/**
	 * @return array{kind: 'content'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'content' ];
	}
}
