<?php

declare(strict_types=1);

namespace Fb\Blocks\Definition\Input\Border;

use Fb\Blocks\Definition\Input\Color\ColorInput;
use Fb\Blocks\Definition\Input\Input;
use Fb\Blocks\Definition\Input\Spacing\SpacingMode;
use Fb\Blocks\Options\BorderStyle;
use Fb\Json\InvalidJson;
use Fb\Json\JsonValue;
use Fb\Values\BorderBox;
use Fb\Values\BorderSide;
use Fb\Values\Color;
use Fb\Values\CustomLength;
use Fb\Values\Lengths;
use Fb\Values\Side;

/**
 * A border: width, style and color per side, edited linked / per axis /
 * per side (like padding). Every part of every side is checked on its own:
 * an invalid one is dropped, the rest survive.
 */
final readonly class BorderInput implements Input
{
	/** @var non-empty-list<SpacingMode> */
	public array $modes;

	public function __construct()
	{
		$this->modes = SpacingMode::cases();
	}

	public function parse( JsonValue $json ): BorderBox
	{
		if ( ! $json->isObject() ) {
			throw new InvalidJson( "{$json->path}: expected an object" );
		}

		$box = new BorderBox(
			self::side( $json, Side::Top ),
			self::side( $json, Side::Right ),
			self::side( $json, Side::Bottom ),
			self::side( $json, Side::Left ),
		);
		if ( $box->isEmpty() ) {
			throw new InvalidJson( "{$json->path}: no valid side" );
		}

		return $box;
	}

	private static function side( JsonValue $box, Side $side ): ?BorderSide
	{
		if ( ! $box->has( $side->value ) || ! $box->get( $side->value )->isObject() ) {
			return null;
		}
		$json   = $box->get( $side->value );
		$parsed = new BorderSide( self::width( $json ), self::style( $json ), self::color( $json ) );

		return $parsed->isEmpty() ? null : $parsed;
	}

	private static function width( JsonValue $side ): ?CustomLength
	{
		try {
			$length = $side->has( 'width' ) ? Lengths::fromJson( $side->get( 'width' ) ) : null;
		} catch ( InvalidJson ) {
			return null;
		}

		return $length instanceof CustomLength && ! $length->isNegative() ? $length : null;
	}

	private static function style( JsonValue $side ): ?BorderStyle
	{
		try {
			return $side->has( 'style' ) ? BorderStyle::tryFrom( $side->get( 'style' )->string() ) : null;
		} catch ( InvalidJson ) {
			return null;
		}
	}

	private static function color( JsonValue $side ): ?Color
	{
		try {
			return $side->has( 'color' ) ? ( new ColorInput() )->parse( $side->get( 'color' ) ) : null;
		} catch ( InvalidJson ) {
			return null;
		}
	}

	/**
	 * The styles offered, with labels (the editor never repeats them).
	 *
	 * @return array{kind: 'border', modes: non-empty-list<SpacingMode>, styles: list<array{value: string, label: string}>}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'   => 'border',
			'modes'  => $this->modes,
			'styles' => array_map( static fn ( BorderStyle $style ): array => [ 'value' => $style->value, 'label' => $style->label() ], BorderStyle::cases() ),
		];
	}
}
