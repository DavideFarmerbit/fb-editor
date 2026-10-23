<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\CssProperty;
use Fb\Styles\DeclarationSet;
use Fb\Values\CssValue;
use Fb\Values\ResponsiveValue;

/**
 * A single value (Length, KeywordValue…) written to one or more properties.
 */
final readonly class PropertyOutput implements StyleOutput
{
	/** @var list<CssProperty> */
	private array $properties;

	public function __construct( CssProperty $property, CssProperty ...$more )
	{
		$this->properties = [ $property, ...array_values( $more ) ];
	}

	public function properties(): array
	{
		return $this->properties;
	}

	public function declarations( ?ResponsiveValue $value ): DeclarationSet
	{
		$declarations = DeclarationSet::empty();

		if ( ! $value instanceof CssValue ) {
			return $declarations;
		}

		foreach ( $this->properties as $property ) {
			$declarations = $declarations->with( $property, $value->toCss() );
		}

		return $declarations;
	}

	/**
	 * @return array{kind: 'property', properties: list<string>}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'       => 'property',
			'properties' => array_map( static fn ( CssProperty $property ): string => $property->value, $this->properties ),
		];
	}
}
