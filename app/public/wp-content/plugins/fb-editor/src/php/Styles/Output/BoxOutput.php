<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\DeclarationSet;
use Fb\Values\BoxValue;
use Fb\Values\ResponsiveValue;
use Fb\Values\Side;

/**
 * BoxValue → `<property>-top/right/bottom/left`.
 */
final readonly class BoxOutput implements StyleOutput
{
	public function __construct(
		public BoxProperty $property,
	) {
	}

	public function properties(): array
	{
		return array_map( $this->property->longhand( ... ), Side::cases() );
	}

	public function declarations( ?ResponsiveValue $value ): DeclarationSet
	{
		$declarations = DeclarationSet::empty();

		if ( ! $value instanceof BoxValue ) {
			return $declarations;
		}

		foreach ( Side::cases() as $side ) {
			$length = $value->get( $side );
			if ( null !== $length ) {
				$declarations = $declarations->with( $this->property->longhand( $side ), $length->toCss() );
			}
		}

		return $declarations;
	}

	/**
	 * @return array{kind: 'box', property: string}
	 */
	public function jsonSerialize(): array
	{
		return [ 'kind' => 'box', 'property' => $this->property->value ];
	}
}
