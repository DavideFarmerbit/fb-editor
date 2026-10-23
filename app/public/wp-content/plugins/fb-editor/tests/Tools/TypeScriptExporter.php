<?php

declare(strict_types=1);

namespace Fb\Tests\Tools;

use Fb\TypeScript\ExportToTypeScript;

/**
 * Writes `<Class>.generated.ts` next to every PHP class that has
 * #[ExportToTypeScript] on itself (enums) or on its constants.
 */
final class TypeScriptExporter
{
	public const string SUFFIX = '.generated.ts';

	public function __construct(
		private readonly string $sourceDir,
	) {
	}

	/**
	 * Expected generated files: absolute path → content.
	 *
	 * @return array<string, string>
	 */
	public function expected(): array
	{
		$files = [];

		foreach ( $this->phpFiles() as $file ) {
			$class = 'Fb\\' . str_replace( '/', '\\', substr( $file, strlen( $this->sourceDir ) + 1, -4 ) );
			if ( ! class_exists( $class ) && ! enum_exists( $class ) && ! interface_exists( $class ) ) {
				continue;
			}

			$body = $this->export( new \ReflectionClass( $class ) );
			if ( '' !== $body ) {
				$files[ substr( $file, 0, -4 ) . self::SUFFIX ] = $this->header( $file ) . $body;
			}
		}

		ksort( $files );

		return $files;
	}

	/**
	 * Generated files on disk that no PHP class produces anymore.
	 *
	 * @return list<string>
	 */
	public function orphans(): array
	{
		$expected = $this->expected();
		$orphans  = [];

		foreach ( $this->files( self::SUFFIX ) as $file ) {
			if ( ! isset( $expected[ $file ] ) ) {
				$orphans[] = $file;
			}
		}

		return $orphans;
	}

	public function write(): void
	{
		foreach ( $this->orphans() as $orphan ) {
			unlink( $orphan );
		}
		foreach ( $this->expected() as $path => $content ) {
			file_put_contents( $path, $content );
		}
	}

	/**
	 * @param \ReflectionClass<object> $class
	 */
	private function export( \ReflectionClass $class ): string
	{
		$out = '';

		$name = $class->getName();
		if ( enum_exists( $name ) && [] !== $class->getAttributes( ExportToTypeScript::class ) ) {
			$out .= $this->enum( $name );
		}

		foreach ( $class->getReflectionConstants() as $constant ) {
			$attribute = $constant->getAttributes( ExportToTypeScript::class )[0] ?? null;
			if ( null === $attribute || $constant->getDeclaringClass()->getName() !== $class->getName() ) {
				continue;
			}
			$out .= $this->constant( $constant, $attribute->newInstance() );
		}

		return $out;
	}

	/**
	 * @param class-string<\UnitEnum> $enumClass
	 */
	private function enum( string $enumClass ): string
	{
		if ( ! is_subclass_of( $enumClass, \BackedEnum::class ) ) {
			throw new \LogicException( "{$enumClass}: only backed enums can be exported." );
		}

		$cases = '';
		foreach ( $enumClass::cases() as $case ) {
			$cases .= "\t{$case->name} = " . $this->literal( $case->value ) . ",\n";
		}

		$shortName = ( new \ReflectionClass( $enumClass ) )->getShortName();

		return "\nexport enum {$shortName} {\n{$cases}}\n";
	}

	private function constant( \ReflectionClassConstant $constant, ExportToTypeScript $options ): string
	{
		$value = $constant->getValue();

		if ( $options->regExp ) {
			if ( ! is_string( $value ) || str_contains( $value, '/' ) ) {
				throw new \LogicException( "{$constant->class}::{$constant->name}: regExp constants must be strings without '/'." );
			}
			$literal = "/{$value}/";
		} else {
			$literal = $this->literal( $value );
		}

		return "\nexport const {$constant->name} = {$literal};\n";
	}

	private function literal( mixed $value ): string
	{
		if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) && ! is_bool( $value ) ) {
			throw new \LogicException( 'Only scalar values can be exported to TypeScript.' );
		}

		$json = json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR );

		// TS style: single-quoted strings.
		return is_string( $value ) ? "'" . str_replace( [ '\\"', "'" ], [ '"', "\\'" ], substr( $json, 1, -1 ) ) . "'" : $json;
	}

	private function header( string $phpFile ): string
	{
		$source = basename( $phpFile );

		return "/**\n * Generated from {$source} by `composer generate`. Do not edit: change the PHP.\n */\n";
	}

	/**
	 * @return list<string>
	 */
	private function phpFiles(): array
	{
		return $this->files( '.php' );
	}

	/**
	 * @return list<string>
	 */
	private function files( string $suffix ): array
	{
		$files    = [];
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->sourceDir, \FilesystemIterator::SKIP_DOTS ) );

		foreach ( $iterator as $file ) {
			if ( $file instanceof \SplFileInfo && str_ends_with( $file->getPathname(), $suffix ) ) {
				$files[] = $file->getPathname();
			}
		}

		sort( $files );

		return $files;
	}
}
