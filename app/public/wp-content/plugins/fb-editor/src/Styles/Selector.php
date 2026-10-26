<?php

declare(strict_types=1);

namespace Fb\Styles;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Zero-specificity selector for a block (or one of its elements):
 *   :where(.fb-k3x9q)  or  :where(.fb-k3x9q>.fb-section__inner)
 *
 * Elements are direct children of the block root, so a nested block of the
 * same type never matches its parent's element rules. Base styles may also
 * target a descendant tag (`:where(.fb-image img)`).
 */
final readonly class Selector implements \Stringable
{
	#[ExportToTypeScript( regExp: true )]
	public const string CLASS_PATTERN = '^-?[_a-zA-Z][_a-zA-Z0-9-]*$';

	/**
	 * Pseudo-classes a root may carry (state selectors, e.g. ":hover", or
	 * ":is(:hover,.fb-preview-hover)" in the editor).
	 */
	#[ExportToTypeScript( regExp: true )]
	public const string PSEUDO_CLASS_PATTERN = '^:[a-z][a-z(),:.-]*$';

	private function __construct(
		/** Inner selector, built only from validated parts. */
		private string $inner,
		/** Still just the root class (+ pseudo-classes): no child yet. */
		private bool $isRoot = true,
	) {
	}

	public static function forClass( string $className ): self
	{
		return new self( '.' . self::validClass( $className ) );
	}

	/** The root, only while it matches this pseudo-class (a state). */
	public function withPseudoClass( string $pseudoClass ): self
	{
		if ( ! $this->isRoot ) {
			throw new \LogicException( 'Pseudo-classes go on the root, before any child.' );
		}
		if ( 1 !== preg_match( '/' . self::PSEUDO_CLASS_PATTERN . '/', $pseudoClass ) ) {
			throw new \InvalidArgumentException( "Invalid pseudo-class \"{$pseudoClass}\"." );
		}

		return new self( $this->inner . $pseudoClass );
	}

	/** A direct child with this class. */
	public function child( string $className ): self
	{
		return new self( "{$this->inner}>." . self::validClass( $className ), false );
	}

	/** Any descendant of this HTML tag. */
	public function descendantTag( string $tag ): self
	{
		if ( 1 !== preg_match( '/^[a-z][a-z0-9]*$/', $tag ) ) {
			throw new \InvalidArgumentException( "Invalid tag name \"{$tag}\"." );
		}

		return new self( "{$this->inner} {$tag}", false );
	}

	public function __toString(): string
	{
		return ":where({$this->inner})";
	}

	private static function validClass( string $className ): string
	{
		if ( 1 !== preg_match( '/' . self::CLASS_PATTERN . '/', $className ) ) {
			throw new \InvalidArgumentException( "Invalid class name \"{$className}\"." );
		}

		return $className;
	}
}
