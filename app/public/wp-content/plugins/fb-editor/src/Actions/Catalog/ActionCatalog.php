<?php

declare(strict_types=1);

namespace Fb\Actions\Catalog;

/**
 * Every trigger, condition and effect type rules may use: what stored rules
 * are parsed against, and what the editor lists. Registered in Plugin, like
 * blocks.
 */
final readonly class ActionCatalog implements \JsonSerializable
{
	/** @var list<ActionType> */
	public array $types;

	public function __construct( ActionType ...$types )
	{
		$seen = [];
		foreach ( $types as $type ) {
			$key = $type->kind()->value . ':' . $type->name();
			if ( isset( $seen[ $key ] ) ) {
				throw new \InvalidArgumentException( "Duplicate {$type->kind()->value} \"{$type->name()}\"." );
			}
			if ( 1 !== preg_match( '/' . ActionType::NAME_PATTERN . '/', $type->name() ) ) {
				throw new \InvalidArgumentException( "Invalid {$type->kind()->value} name \"{$type->name()}\"." );
			}
			$ids = [];
			foreach ( $type->params() as $param ) {
				if ( isset( $ids[ $param->id ] ) ) {
					throw new \InvalidArgumentException( "{$type->name()}: duplicate parameter \"{$param->id}\"." );
				}
				$ids[ $param->id ] = true;
			}
			$seen[ $key ] = true;
		}

		$this->types = array_values( $types );
	}

	public function find( ActionKind $kind, string $name ): ?ActionType
	{
		foreach ( $this->types as $type ) {
			if ( $type->kind() === $kind && $type->name() === $name ) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * @return list<ActionType>
	 */
	public function jsonSerialize(): array
	{
		return $this->types;
	}
}
