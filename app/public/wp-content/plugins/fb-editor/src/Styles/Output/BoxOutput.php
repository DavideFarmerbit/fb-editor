<?php

declare(strict_types=1);

namespace Fb\Styles\Output;

use Fb\Styles\DeclarationSet;
use Fb\Values\BoxValue;
use Fb\Values\ResponsiveValue;
use Fb\Values\Side;

/**
 * BoxValue → one longhand per side (see BoxProperty).
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
	 * The longhand of each side, computed here so the editor never
	 * re-derives property names.
	 *
	 * @return array{kind: 'box', properties: array{top: string, right: string, bottom: string, left: string}}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'       => 'box',
			'properties' => [
				'top'    => $this->property->longhand( Side::Top )->value,
				'right'  => $this->property->longhand( Side::Right )->value,
				'bottom' => $this->property->longhand( Side::Bottom )->value,
				'left'   => $this->property->longhand( Side::Left )->value,
			],
		];
	}
}
