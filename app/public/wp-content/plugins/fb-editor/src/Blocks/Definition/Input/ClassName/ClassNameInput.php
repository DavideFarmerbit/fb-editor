<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\ClassName;

use Fb\Blocks\Definition\Input\Input;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\KeywordValue;

/**
 * One HTML class name an action adds or checks ("is-active"): lowercase
 * letters, digits and dashes, so it's also safe in a selector.
 */
final readonly class ClassNameInput implements Input
{
	public function parse( JsonValue $json ): KeywordValue
	{
		$class = trim( $json->string() );
		if ( 1 !== preg_match( '/' . KeywordValue::PATTERN . '/', $class ) ) {
			throw new InvalidJson( "{$json->path}: not a class name (lowercase letters, digits, -)" );
		}

		return new KeywordValue( $class );
	}

	/**
	 * @return array{kind: 'class-name'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'class-name' ];
	}
}
