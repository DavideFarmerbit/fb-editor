<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Values\ResponsiveValue;
use Fb\Values\TextValue;

/**
 * Text as a CSS string: `content: "→"`. Backslashes and quotes are
 * escaped; so is "<", so content can never close the <style> it's in.
 */
final readonly class ContentOutput implements StyleOutput
{
	public function properties(): array
	{
		return [ CssProperty::Content ];
	}

	public function declarations( ?ResponsiveValue $value ): DeclarationSet
	{
		if ( ! $value instanceof TextValue ) {
			return DeclarationSet::empty();
		}

		$escaped = strtr( $value->text, [ '\\' => '\\\\', '"' => '\\"', '<' => '\\3C ' ] );

		return DeclarationSet::empty()->with( CssProperty::Content, "\"{$escaped}\"" );
	}

	/**
	 * @return array{kind: 'content'}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'content' ];
	}
}
