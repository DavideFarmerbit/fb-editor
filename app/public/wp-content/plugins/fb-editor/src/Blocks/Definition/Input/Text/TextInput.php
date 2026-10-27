<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Text;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\TextValue;

/**
 * One line of plain text (tags stripped, whitespace collapsed).
 */
final readonly class TextInput implements Input
{
	#[ExportToTypeScript]
	public const int MAX_LENGTH = 200;

	public function parse( JsonValue $json ): TextValue
	{
		$text = sanitize_text_field( $json->string() );
		if ( '' === $text ) {
			throw new InvalidJson( "{$json->path}: empty text" );
		}

		return new TextValue( mb_substr( $text, 0, self::MAX_LENGTH ) );
	}

	/**
	 * @return array{kind: 'text'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'text' ];
	}
}
