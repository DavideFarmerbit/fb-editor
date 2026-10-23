<?php

declare(strict_types=1);

namespace Fb\Json;

/**
 * Typed, read-only view over decoded JSON.
 *
 * This is the boundary: raw JSON goes in, typed scalars and lists come out,
 * and any shape mismatch raises InvalidJson with the exact path.
 */
final readonly class JsonValue
{
	private function __construct(
		private mixed $value,
		public string $path,
	) {
	}

	/**
	 * @throws InvalidJson
	 */
	public static function parse( string $json, string $source ): self
	{
		try {
			$value = json_decode( $json, false, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			throw new InvalidJson( "{$source}: {$e->getMessage()}", 0, $e );
		}

		return new self( $value, $source );
	}

	public function has( string $key ): bool
	{
		return $this->value instanceof \stdClass && property_exists( $this->value, $key );
	}

	/**
	 * @throws InvalidJson
	 */
	public function get( string $key ): self
	{
		if ( ! $this->value instanceof \stdClass ) {
			throw new InvalidJson( "{$this->path}: expected an object" );
		}
		if ( ! property_exists( $this->value, $key ) ) {
			throw new InvalidJson( "{$this->path}.{$key}: missing" );
		}

		return new self( $this->value->{$key}, "{$this->path}.{$key}" );
	}

	/**
	 * @throws InvalidJson
	 */
	public function string(): string
	{
		if ( ! is_string( $this->value ) ) {
			throw new InvalidJson( "{$this->path}: expected a string" );
		}

		return $this->value;
	}

	/**
	 * @throws InvalidJson
	 */
	public function int(): int
	{
		if ( ! is_int( $this->value ) ) {
			throw new InvalidJson( "{$this->path}: expected an integer" );
		}

		return $this->value;
	}

	/**
	 * @return list<self>
	 * @throws InvalidJson
	 */
	public function list(): array
	{
		if ( ! is_array( $this->value ) || ! array_is_list( $this->value ) ) {
			throw new InvalidJson( "{$this->path}: expected an array" );
		}

		$items = [];
		foreach ( $this->value as $index => $item ) {
			$items[] = new self( $item, "{$this->path}[{$index}]" );
		}

		return $items;
	}

	/**
	 * @throws InvalidJson
	 */
	public function optionalString( string $key ): ?string
	{
		return $this->has( $key ) ? $this->get( $key )->string() : null;
	}

	/**
	 * @throws InvalidJson
	 */
	public function optionalInt( string $key ): ?int
	{
		return $this->has( $key ) ? $this->get( $key )->int() : null;
	}
}
