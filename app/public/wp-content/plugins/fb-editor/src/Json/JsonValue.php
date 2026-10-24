<?php

declare(strict_types=1);

namespace Fb\Json;

/**
 * Typed, read-only view over decoded JSON.
 *
 * This is the boundary: raw JSON goes in, typed scalars and lists come out,
 * and any shape mismatch raises InvalidJson with the exact path.
 *
 * Accepts both `json_decode()` output (objects as stdClass) and data WordPress
 * already decoded (block attributes: objects as string-keyed arrays).
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

	/**
	 * Wraps data that is already decoded (e.g. block attributes).
	 */
	public static function of( mixed $decoded, string $source ): self
	{
		return new self( $decoded, $source );
	}

	/**
	 * The raw decoded data, to hand to another boundary parser (e.g. a
	 * defaults section to BlockValues). Never read fields from it directly.
	 */
	public function decoded(): mixed
	{
		return $this->value;
	}

	public function isNull(): bool
	{
		return null === $this->value;
	}

	public function isObject(): bool
	{
		return $this->value instanceof \stdClass
			|| ( is_array( $this->value ) && ( [] === $this->value || ! array_is_list( $this->value ) ) );
	}

	public function has( string $key ): bool
	{
		return match ( true ) {
			$this->value instanceof \stdClass => property_exists( $this->value, $key ),
			is_array( $this->value )          => array_key_exists( $key, $this->value ),
			default                           => false,
		};
	}

	/**
	 * @throws InvalidJson
	 */
	public function get( string $key ): self
	{
		if ( ! $this->isObject() ) {
			throw new InvalidJson( "{$this->path}: expected an object" );
		}
		if ( ! $this->has( $key ) ) {
			throw new InvalidJson( "{$this->path}.{$key}: missing" );
		}

		$child = $this->value instanceof \stdClass ? $this->value->{$key} : ( is_array( $this->value ) ? $this->value[ $key ] : null );

		return new self( $child, "{$this->path}.{$key}" );
	}

	/**
	 * Object members in document order.
	 *
	 * @return \Generator<string, self>
	 * @throws InvalidJson
	 */
	public function properties(): \Generator
	{
		if ( ! $this->isObject() ) {
			throw new InvalidJson( "{$this->path}: expected an object" );
		}

		/** @var iterable<mixed, mixed> $members */
		$members = $this->value instanceof \stdClass ? get_object_vars( $this->value ) : $this->value;

		foreach ( $members as $key => $child ) {
			$name = is_int( $key ) ? (string) $key : ( is_string( $key ) ? $key : '' );
			yield $name => new self( $child, "{$this->path}.{$name}" );
		}
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
	 * Any JSON number. Rejects NAN/INF.
	 *
	 * @throws InvalidJson
	 */
	public function float(): float
	{
		if ( ! is_int( $this->value ) && ! is_float( $this->value ) ) {
			throw new InvalidJson( "{$this->path}: expected a number" );
		}
		if ( ! is_finite( (float) $this->value ) ) {
			throw new InvalidJson( "{$this->path}: expected a finite number" );
		}

		return (float) $this->value;
	}

	/**
	 * @throws InvalidJson
	 */
	public function bool(): bool
	{
		if ( ! is_bool( $this->value ) ) {
			throw new InvalidJson( "{$this->path}: expected a boolean" );
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
